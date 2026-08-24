import re
import json
from collections import defaultdict

# Read the routes file
with open('C:/xampp/htdocs/unihub/unihub-API/routes/api.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove comments to simplify parsing
lines = content.split('\n')
cleaned_lines = []

in_multiline_comment = False
for line in lines:
    stripped = line.strip()
    
    # Handle multiline comments
    if stripped.startswith('/*'):
        in_multiline_comment = True
        if '*/' in stripped:
            in_multiline_comment = False
        continue
    elif in_multiline_comment:
        if '*/' in stripped:
            in_multiline_comment = False
        continue
    
    # Skip single line comments
    if stripped.startswith('//') or stripped.startswith('#'):
        continue
        
    # Skip empty lines
    if not stripped:
        continue
        
    cleaned_lines.append(line)

# Now parse the cleaned content
content_clean = '\n'.join(cleaned_lines)

# Extract route groups and their prefixes/middleware
routes = []

# Pattern to match Route::get, Route::post, etc.
route_pattern = re.compile(
    r'Route::(get|post|put|delete|patch|options|match)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\s*\)'
)

# Pattern to match Route::controller
controller_pattern = re.compile(
    r'Route::controller\s*\(\s*([^,\s]+)\s*\.\s*class\s*,\s*function\s*\(\s*\)\s*\)\s*\{'
)

# Pattern to match Route::apiResource
resource_pattern = re.compile(
    r'Route::apiResource\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^,\s]+)\s*::class\s*(?:->\s*only\s*\(\s*\[([^\]]+)\]\s*)?\)'
)

# Pattern to match Route::prefix
prefix_pattern = re.compile(
    r'Route::prefix\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->\s*group\s*\(\s*function\s*\(\s*\)\s*\)'
)

# Pattern to match Route::middleware
middleware_pattern = re.compile(
    r'Route::middleware\s*\(\s*\[([^\]]+)\]\s*\)\s*->\s*(?:prefix\s*\([^)]+\)\s*->\s*)?group\s*\(\s*function\s*\(\s*\)\s*\)'
)

# Find all route definitions
for match in route_pattern.finditer(content_clean):
    method = match.group(1).upper()
    path = match.group(2)
    action = match.group(3).strip()
    
    # Clean up the action
    action = re.sub(r'\s+', ' ', action)
    
    routes.append({
        'method': method,
        'path': path,
        'action': action,
        'type': 'direct'
    })

# Find apiResource routes
for match in resource_pattern.finditer(content_clean):
    path = match.group(1)
    controller = match.group(2)
    only_params = match.group(3) if match.group(3) else None
    
    # Standard RESTful routes for apiResource
    resource_routes = [
        ('GET', path, 'index'),
        ('POST', path, 'store'),
        ('GET', f'{path}/{{{path[:-1] if path.endswith("s") else path}}}', 'show'),
        ('PUT/PATCH', f'{path}/{{{path[:-1] if path.endswith("s") else path}}}', 'update'),
        ('DELETE', f'{path}/{{{path[:-1] if path.endswith("s") else path}}}', 'destroy')
    ]
    
    if only_params:
        # Filter based on only parameters
        allowed = [p.strip().strip('\'"') for p in only_params.split(',')]
        resource_routes = [r for r in resource_routes if r[2] in allowed]
    
    for method, route_path, action_method in resource_routes:
        routes.append({
            'method': method,
            'path': route_path,
            'action': f'{controller}@{action_method}',
            'type': 'resource'
        })

print(f"Found {len(routes)} total routes")

# Group by controller for better organization
routes_by_controller = defaultdict(list)
for route in routes:
    action = route['action']
    if '@' in action:
        controller = action.split('@')[0]
        method_name = action.split('@')[1] if len(action.split('@')) > 1 else 'unknown'
        routes_by_controller[controller].append({
            'method': route['method'],
            'path': route['path'],
            'action_method': method_name,
            'full_action': action,
            'type': route['type']
        })
    else:
        routes_by_controller['unknown'].append(route)

# Extract commented routes from original content
commented_routes = []
lines_orig = content.split('\n')

for i, line in enumerate(lines_orig):
    line_num = i + 1
    stripped = line.strip()
    
    # Check if line contains Route:: and is commented
    if 'Route::' in line:
        # Check if it's commented
        if '//' in line:
            comment_pos = line.index('//')
            route_pos = line.index('Route::')
            if comment_pos < route_pos:
                commented_routes.append((line_num, line))
        elif stripped.startswith('/*') or stripped.startswith('*'):
            commented_routes.append((line_num, line))
        elif line.startswith('#'):
            comment_pos = line.index('#')
            route_pos = line.find('Route::')
            if route_pos != -1 and comment_pos < route_pos:
                commented_routes.append((line_num, line))

print(f"Found {len(commented_routes)} commented route lines")

# Save the routes to a file for further processing
routes_data = {
    'active_routes': routes,
    'routes_by_controller': dict(routes_by_controller),
    'commented_routes': [{'line': num, 'content': line.strip()} for num, line in commented_routes]
}

with open('/c/xampp/htdocs/unihub/unihub-API/docs/routes_parsed.json', 'w', encoding='utf-8') as f:
    json.dump(routes_data, f, indent=2, ensure_ascii=False)

print("Saved parsed routes to docs/routes_parsed.json")