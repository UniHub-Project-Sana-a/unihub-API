<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class RoutesController extends Controller
{
    /**
     * عرض جميع Routes مع تفاصيل كاملة
     */
    public function index(Request $request)
    {
        $allRoutes = Route::getRoutes();
        $routes = collect([]);

        foreach ($allRoutes as $route) {
            // تحليل Route للحصول على التفاصيل
            $routeDetails = $this->analyzeRoute($route);
            
            if ($routeDetails) {
                $routes->push($routeDetails);
            }
        }

        // الفلاتر
        $routes = $this->applyFilters($routes, $request);

        // الإحصائيات المتقدمة
        $stats = $this->calculateStats($routes);

        // تجميع حسب Controller
        $groupedRoutes = $routes->groupBy('controller');

        return view('admin.routes.index', compact('routes', 'stats', 'groupedRoutes'));
    }

    /**
     * تحليل Route للحصول على جميع التفاصيل
     */
    private function analyzeRoute($route)
    {
        $action = $route->getActionName();
        
        // تخطي routes النظام
        if (Str::startsWith($route->uri(), ['_debugbar', '_ignition', 'sanctum/csrf', 'telescope'])) {
            return null;
        }

        // استخراج Controller و Method
        [$controller, $method] = $this->extractControllerMethod($action);

        // تحليل التوثيق من PHPDoc
        $documentation = $this->getMethodDocumentation($controller, $method);

        // استخراج Parameters المطلوبة
        $parameters = $this->extractParameters($route, $controller, $method);

        // استخراج Validation Rules
        $validation = $this->extractValidationRules($controller, $method);

        return [
            'methods' => array_diff($route->methods(), ['HEAD']),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $action,
            'controller' => $controller,
            'method' => $method,
            'middleware' => $route->middleware(),
            'parameters' => $parameters,
            'validation' => $validation,
            'documentation' => $documentation,
            'is_api' => Str::startsWith($route->uri(), 'api/'),
            'is_public' => !in_array('auth', $route->middleware()) && !in_array('auth:sanctum', $route->middleware()),
            'requires_admin' => in_array('role:admin', $route->middleware()),
            'rate_limit' => $this->extractRateLimit($route),
        ];
    }

    /**
     * استخراج Controller و Method
     */
    private function extractControllerMethod($action)
    {
        if (Str::contains($action, '@')) {
            return explode('@', $action);
        } elseif (Str::contains($action, '::')) {
            [$controller, $method] = explode('::', str_replace(['App\\Http\\Controllers\\', 'Closure'], '', $action));
            return [$controller, $method ?? '__invoke'];
        }
        
        return [null, 'Closure'];
    }

    /**
     * الحصول على التوثيق من PHPDoc
     */
    private function getMethodDocumentation($controller, $method)
    {
        if (!$controller || $method === 'Closure') {
            return null;
        }

        try {
            $fullController = "App\\Http\\Controllers\\{$controller}";
            
            if (!class_exists($fullController)) {
                return null;
            }

            $reflection = new ReflectionClass($fullController);
            
            if (!$reflection->hasMethod($method)) {
                return null;
            }

            $reflectionMethod = $reflection->getMethod($method);
            $docComment = $reflectionMethod->getDocComment();

            if (!$docComment) {
                return null;
            }

            // استخراج الوصف
            preg_match('/@description\s+(.+)/', $docComment, $descMatches);
            preg_match('/@param\s+(.+)/', $docComment, $paramMatches);
            preg_match('/@return\s+(.+)/', $docComment, $returnMatches);
            preg_match('/@throws\s+(.+)/', $docComment, $throwsMatches);

            return [
                'description' => $descMatches[1] ?? null,
                'params' => $paramMatches[1] ?? null,
                'return' => $returnMatches[1] ?? null,
                'throws' => $throwsMatches[1] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * استخراج Parameters من URI
     */
    private function extractParameters($route, $controller, $method)
    {
        $params = [];

        // Parameters من URI
        preg_match_all('/\{([^}]+)\}/', $route->uri(), $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $param) {
                $params[] = [
                    'name' => str_replace('?', '', $param),
                    'required' => !Str::endsWith($param, '?'),
                    'type' => 'path',
                ];
            }
        }

        // Parameters من Method Signature
        if ($controller && $method !== 'Closure') {
            try {
                $fullController = "App\\Http\\Controllers\\{$controller}";
                $reflection = new ReflectionClass($fullController);
                
                if ($reflection->hasMethod($method)) {
                    $reflectionMethod = $reflection->getMethod($method);
                    
                    foreach ($reflectionMethod->getParameters() as $param) {
                        if ($param->getName() !== 'request') {
                            $params[] = [
                                'name' => $param->getName(),
                                'required' => !$param->isOptional(),
                                'type' => 'method',
                                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        return $params;
    }

    /**
     * استخراج Validation Rules (إذا كان موجود FormRequest)
     */
    private function extractValidationRules($controller, $method)
    {
        // هذه دالة معقدة، يمكن توسيعها لاحقاً
        // تحتاج لتحليل FormRequest المستخدم في الـ Controller
        return null;
    }

    /**
     * استخراج Rate Limit
     */
    private function extractRateLimit($route)
    {
        foreach ($route->middleware() as $middleware) {
            if (Str::startsWith($middleware, 'throttle:')) {
                return str_replace('throttle:', '', $middleware);
            }
        }
        
        return null;
    }

    /**
     * تطبيق الفلاتر
     */
    private function applyFilters($routes, $request)
    {
        if ($search = $request->get('search')) {
            $routes = $routes->filter(function ($route) use ($search) {
                return Str::contains($route['uri'], $search) ||
                       Str::contains($route['name'] ?? '', $search) ||
                       Str::contains($route['controller'] ?? '', $search);
            });
        }

        if ($method = $request->get('method')) {
            $routes = $routes->filter(function ($route) use ($method) {
                return in_array(strtoupper($method), $route['methods']);
            });
        }

        if ($request->has('is_api')) {
            $routes = $routes->filter(fn($r) => $r['is_api']);
        }

        if ($request->has('is_public')) {
            $routes = $routes->filter(fn($r) => $r['is_public']);
        }

        if ($request->has('requires_admin')) {
            $routes = $routes->filter(fn($r) => $r['requires_admin']);
        }

        return $routes;
    }

    /**
     * حساب الإحصائيات المتقدمة
     */
    private function calculateStats($routes)
    {
        return [
            'total' => $routes->count(),
            'get' => $routes->filter(fn($r) => in_array('GET', $r['methods']))->count(),
            'post' => $routes->filter(fn($r) => in_array('POST', $r['methods']))->count(),
            'put' => $routes->filter(fn($r) => in_array('PUT', $r['methods']))->count(),
            'delete' => $routes->filter(fn($r) => in_array('DELETE', $r['methods']))->count(),
            'patch' => $routes->filter(fn($r) => in_array('PATCH', $r['methods']))->count(),
            'api' => $routes->filter(fn($r) => $r['is_api'])->count(),
            'web' => $routes->filter(fn($r) => !$r['is_api'])->count(),
            'public' => $routes->filter(fn($r) => $r['is_public'])->count(),
            'protected' => $routes->filter(fn($r) => !$r['is_public'])->count(),
            'admin_only' => $routes->filter(fn($r) => $r['requires_admin'])->count(),
            'with_rate_limit' => $routes->filter(fn($r) => $r['rate_limit'] !== null)->count(),
            'unique_controllers' => $routes->pluck('controller')->filter()->unique()->count(),
        ];
    }
}