import json
import os
from datetime import datetime

# Read the parsed routes data
with open('routes_parsed.json', 'r', encoding='utf-8') as f:
    routes_data = json.load(f)

# Get the active routes
active_routes = routes_data.get('active_routes', [])
routes_by_controller = routes_data.get('routes_by_controller', {})
commented_routes = routes_data.get('commented_routes', [])

# Documentation template
generation_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

doc_content = "# UniHub API Documentation\n\n"
doc_content += "**Generated on:** " + generation_time + "\n"
doc_content += "**Base URL:** `\api\v1`\n"
doc_content += "**Authentication:** Bearer Token (Laravel Passport)\n\n"
doc_content += "## Table of Contents\n"
doc_content += "1. [Authentication](#authentication)\n"
doc_content += "2. [User Management](#user-management)\n"
doc_content += "3. [College Management](#college-management)\n"
doc_content += "4. [Department Management](#department-management)\n"
doc_content += "5. [Program Management](#program-management)\n"
doc_content += "6. [Level Management](#level-management)\n"
doc_content += "7. [Semester Management](#semester-management)\n"
doc_content += "8. [Course Management](#course-management)\n"
doc_content += "9. [Building Management](#building-management)\n"
doc_content += "10. [Classroom Management](#classroom-management)\n"
doc_content += "11. [Block Management](#block-management)\n"
doc_content += "12. [Academic Title Management](#academic-title-management)\n"
doc_content += "13. [Lecturer Management](#lecturer-management)\n"
doc_content += "14. [Student Management](#student-management)\n"
doc_content += "15. [Student Group Management](#student-group-management)\n"
doc_content += "16. [Attendance Management](#attendance-management)\n"
doc_content += "17. [Lecturer Attendance Management](#lecturer-attendance-management)\n"
doc_content += "18. [Timetable Management](#timetable-management)\n"
doc_content += "19. [Lecture Session Management](#lecture-session-management)\n"
doc_content += "20. [QR Code Management](#qr-code-management)\n"
doc_content += "21. [Lookup Tables](#lookup-tables)\n"
doc_content += "22. [Dashboard & Reports](#dashboard--reports)\n"
doc_content += "23. [Financial Management](#financial-management)\n"
doc_content += "24. [Quality Assurance](#quality-assurance)\n"
doc_content += "25. [Sync Management](#sync-management)\n"
doc_content += "26. [Commented\Inactive Routes](#commented-inactive-routes)\n\n"
doc_content += "---\n\n"

# Authentication section
doc_content += "## Authentication\n\n"
doc_content += "### Login\n"
doc_content += "- **URL:** `\api\v1\auth\login`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Authenticate user and receive access token\n"
doc_content += "- **Middleware:** `throttle:login`\n"
doc_content += "- **Request Body:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"email\": \"string (required)\",\n"
doc_content += "    \"password\": \"string (required)\",\n"
doc_content += "    \"mac_address\": \"string (required, max:100)\",\n"
doc_content += "    \"device_name\": \"string (required, max:100)\",\n"
doc_content += "    \"os_type\": \"string (required, max:50)\"\n"
doc_content += "  }\n"
doc_content += "  ```\n"
doc_content += "- **Success Response:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"access_token\": \"string\",\n"
doc_content += "    \"token_type\": \"Bearer\",\n"
doc_content += "    \"expires_in\": 3600,\n"
doc_content += "    \"user\": {\n"
doc_content += "      \"user_id\": \"integer\",\n"
doc_content += "      \"full_name\": \"string\",\n"
doc_content += "      \"email\": \"string\",\n"
doc_content += "      \"phone\": \"string\",\n"
doc_content += "      \"academic_number\": \"string\",\n"
doc_content += "      \"gender\": \"integer (1=male, 2=female)\",\n"
doc_content += "      \"userType\": {\n"
doc_content += "        \"user_type_id\": \"integer\",\n"
doc_content += "        \"user_type_name\": \"string\",\n"
doc_content += "        \"user_type_code\": \"string (student|lecturer|admin)\"\n"
doc_content += "      },\n"
doc_content += "      \"college\": {\n"
doc_content += "        \"college_id\": \"integer\",\n"
doc_content += "        \"college_name\": \"string\"\n"
doc_content += "      }\n"
doc_content += "    }\n"
doc_content += "  }\n"
doc_content += "  ```\n"
doc_content += "- **Error Responses:**\n"
doc_content += "  - `401`: Invalid credentials\n"
doc_content += "  - `422`: Validation error\n"
doc_content += "  - `429`: Too Many Requests (throttle)\n\n"

doc_content += "### Verify OTP\n"
doc_content += "- **URL:** `\api\v1\auth\verify-otp`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Verify OTP for new device login\n"
doc_content += "- **Request Body:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"verification_id\": \"integer (required)\",\n"
doc_content += "    \"otp_code\": \"string (required, exactly 6 digits)\"\n"
doc_content += "  }\n"
doc_content += "  ```\n"
doc_content += "- **Success Response:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"access_token\": \"string\",\n"
doc_content += "    \"token_type\": \"Bearer\",\n"
doc_content += "    \"user\": { \* User object *\ }\n"
doc_content += "  }\n"
doc_content += "  ```\n\n"

doc_content += "### Forgot Password\n"
doc_content += "- **URL:** `\api\v1\auth\forgot-password`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Request password reset link\n"
doc_content += "- **Request Body:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"email\": \"string (required)\"\n"
doc_content += "  }\n"
doc_content += "  ```\n\n"

doc_content += "### Reset Password\n"
doc_content += "- **URL:** `\api\v1\auth\reset-password`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Reset password with token\n"
doc_content += "- **Middleware:** `throttle:reset`\n"
doc_content += "- **Request Body:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"email\": \"string (required)\",\n"
doc_content += "    \"password\": \"string (required|confirmed|min:8)\",\n"
doc_content += "    \"password_confirmation\": \"string (required)\",\n"
doc_content += "    \"token\": \"string (required)\"\n"
doc_content += "  }\n"
doc_content += "  ```\n\n"

doc_content += "### Get Authenticated User\n"
doc_content += "- **URL:** `\api\v1\auth\me`\n"
doc_content += "- **Method:** `GET`\n"
doc_content += "- **Description:** Get currently authenticated user profile\n"
doc_content += "- **Middleware:** `auth:api`\n"
doc_content += "- **Success Response:** User object (same as login response)\n\n"

doc_content += "### Logout\n"
doc_content += "- **URL:** `\api\v1\auth\logout`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Logout user and revoke token\n"
doc_content += "- **Middleware:** `auth:api`\n"
doc_content += "- **Success Response:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"message\": \"تم تسجيل الخروج بنجاح.\"\n"
doc_content += "  }\n"
doc_content += "  ```\n\n"

doc_content += "### Change Password\n"
doc_content += "- **URL:** `\api\v1\auth\change-password`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Change password for authenticated user\n"
doc_content += "- **Middleware:** `auth:api`\n"
doc_content += "- **Request Body:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"password\": \"string (required|confirmed|min:8)\"\n"
doc_content += "  }\n"
doc_content += "  ```\n"
doc_content += "- **Success Response:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"status\": true,\n"
doc_content += "    \"message\": \"تم تغيير كلمة المرور بنجاح。\"\n"
doc_content += "  }\n"
doc_content += "  ```\n\n"

doc_content += "### Refresh Token\n"
doc_content += "- **URL:** `\api\v1\auth\refresh`\n"
doc_content += "- **Method:** `POST`\n"
doc_content += "- **Description:** Refresh access token using refresh token\n"
doc_content += "- **Request Body:**\n"
doc_content += "  ```json\n"
doc_content += "  {\n"
doc_content += "    \"refresh_token\": \"string (required)\"\n"
doc_content += "  }\n"
doc_content += "  ```\n"
doc_content += "- **Success Response:** New access token object\n\n"

doc_content += "---\n\n"

# User Management section
doc_content += "## User Management\n\n"
doc_content += "### Users Controller\n\n"

# Add routes for each controller
for controller_name, controller_routes in sorted(routes_by_controller.items()):
    if controller_name == 'unknown':
        continue
        
    doc_content += "### " + controller_name + "\n\n"
    
    # Group routes by method for cleaner display
    routes_by_method = {}
    for route in controller_routes:
        method = route['method']
        if method not in routes_by_method:
            routes_by_method[method] = []
        routes_by_method[method].append(route)
    
    for method in sorted(routes_by_method.keys()):
        doc_content += "#### " + method + " Operations\n"
        for route in routes_by_method[method]:
            path = route['path']
            action_method = route['action_method']
            doc_content += "- **" + method + "** `" + path + "`\n"
            doc_content += "  - Action: `" + action_method + "`\n"
            
            # Add description based on action method
            descriptions = {
                'index': 'Retrieve list of resources',
                'store': 'Create a new resource',
                'show': 'Retrieve a specific resource',
                'update': 'Update a specific resource',
                'destroy': 'Delete a specific resource',
                'getFinancialDues': 'Get financial dues for lecturer',
                'importCsv': 'Import data from CSV file',
                'importExternal': 'Import data from external source',
                'dashboard': 'Get dashboard statistics',
                'me': 'Get current user profile',
                'login': 'Authenticate user',
                'verifyOtp': 'Verify OTP code',
                'forgot': 'Request password reset',
                'reset': 'Reset password',
                'logout': 'Logout user',
                'changePassword': 'Change user password',
                'bulkMoveStudents': 'Bulk move students between groups',
                'upsertAndAttach': 'Create or get group and attach students',
                'detachStudent': 'Remove student from group',
                'students': 'Get students in a group',
                'storeBulk': 'Create multiple lecture sessions',
                'getSchedulableLectures': 'Get lectures available for scheduling',
                'startSession': 'Start QR code session',
                'refresh': 'Refresh QR code',
                'endSession': 'End QR code session',
                'extendDuration': 'Extend QR code session duration',
                'scan': 'Scan QR code for attendance',
                'manualEntry': 'Manual attendance entry',
                'getGroupStudents': 'Get students for a group',
                'checkIn': 'Lecturer check-in',
                'finalizeSession': 'Finalize lecture session',
                'getPendingEvaluations': 'Get pending evaluations',
                'submitEvaluation': 'Submit evaluation',
                'getEvaluationForm': 'Get evaluation form',
                'getCampaignSummary': 'Get QA campaign summary',
                'getCampaignTimetables': 'Get QA campaign timetables',
                'storeOutcome': 'Store QA outcome',
                'updateOutcome': 'Update QA outcome',
                'destroyOutcome': 'Delete QA outcome',
                'storeTopic': 'Store QA topic',
                'updateTopic': 'Update QA topic',
                'destroyTopic': 'Delete QA topic',
                'storeQuestion': 'Store QA question',
                'updateQuestion': 'Update QA question',
                'destroyQuestion': 'Delete QA question',
                'store': 'Store lecture attachment',
                'index': 'Get lecture attachments',
                'update': 'Update lecture attachment',
                'destroy': 'Delete lecture attachment',
                'getCourseQaData': 'Get course QA data',
                'storeOutcome': 'Store QA outcome',
                'updateOutcome': 'Update QA outcome',
                'destroyOutcome': 'Delete QA outcome',
                'storeTopic': 'Store QA topic',
                'updateTopic': 'Update QA topic',
                'destroyTopic': 'Delete QA topic',
                'storeQuestion': 'Store QA question',
                'updateQuestion': 'Update QA question',
                'destroyQuestion': 'Delete QA question',
                'getTopicsStatus': 'Get topics status for lecture session',
                'getSchedule': 'Get lecturer schedule',
                'getPolicy': 'Get security policy',
                'updatePolicy': 'Update security policy',
                'sessions': 'Get active sessions',
                'revokeSession': 'Revoke session',
                'auditLogs': 'Get audit logs',
                'getCycleByMonth': 'Get financial cycle by month',
                'generateCycle': 'Generate financial cycle',
                'addAdjustment': 'Add financial adjustment',
                'updateStatus': 'Update financial cycle status',
                'getCreationMeta': 'Get QA campaign creation meta',
                'getYearDetails': 'Get QA campaign year details',
                'index': 'Get QA forms',
                'store': 'Store QA form',
                'show': 'Show QA form',
                'update': 'Update QA form',
                'destroy': 'Destroy QA form',
                'getCourseQaData': 'Get course QA data',
                'getTopicsStatus': 'Get topics status',
                'storeOutcome': 'Store QA outcome',
                'updateOutcome': 'Update QA outcome',
                'destroyOutcome': 'Destroy QA outcome',
                'storeTopic': 'Store QA topic',
                'updateTopic': 'Update QA topic',
                'destroyTopic': 'Destroy QA topic',
                'storeQuestion': 'Store QA question',
                'updateQuestion': 'Update QA question',
                'destroyQuestion': 'Delete QA question',
                'index': 'Get lecture attachments',
                'store': 'Store lecture attachment',
                'update': 'Update lecture attachment',
                'destroy': 'Destroy lecture attachment',
                'index': 'Get university comprehensive report'
            }
            
            description = descriptions.get(action_method, 'Perform ' + action_method + ' action')
            doc_content += "  - Description: " + description + "\n"
            
            # Add notes for specific routes
            if 'middleware' in str(route):
                doc_content += "  - Notes: Requires authentication\n"
            
            doc_content += "\n"

# Add commented routes section
doc_content += "---\n\n"
doc_content += "## Commented\Inactive Routes\n\n"
doc_content += "The following routes are currently commented out in the codebase and may be activated in the future:\n\n"

for comment in commented_routes[:20]:  # Limit to first 20
    doc_content += "- Line " + str(comment['line']) + ": `" + comment['content'].strip() + "`\n"

if len(commented_routes) > 20:
    doc_content += "\n... and " + str(len(commented_routes) - 20) + " more commented routes.\n"

doc_content += "\n\n---\n\n"

# Response Formats section
doc_content += "## Response Formats\n\n"
doc_content += "### Success Responses\n"
doc_content += "- **200 OK**: Request successful\n"
doc_content += "- **201 Created**: Resource created successfully\n"
doc_content += "- **202 Accepted**: Request accepted for processing\n\n"

doc_content += "### Error Responses\n"
doc_content += "- **400 Bad Request**: Invalid request syntax or invalid request message framing\n"
doc_content += "- **401 Unauthorized**: Authentication required or failed\n"
doc_content += "- **403 Forbidden**: Authenticated user does not have permission\n"
doc_content += "- **404 Not Found**: Resource not found\n"
doc_content += "- **405 Method Not Allowed**: HTTP method not supported for this resource\n"
doc_content += "- **409 Conflict**: Request conflicts with current state of server\n"
doc_content += "- **422 Unprocessable Entity**: Validation error\n"
doc_content += "- **429 Too Many Requests**: Rate limit exceeded\n"
doc_content += "- **500 Internal Server Error**: Unexpected server error\n"
doc_content += "- **501 Not Implemented**: Server does not support functionality\n"
doc_content += "- **503 Service Unavailable**: Server temporarily unavailable\n\n"

doc_content += "### Error Response Format\n"
doc_content += "```json\n"
doc_content += "{\n"
doc_content += "  \"message\": \"Error description\",\n"
doc_content += "  \"status_code\": 400,\n"
doc_content += "  \"errors\": { \* Validation errors if applicable *\ }\n"
doc_content += "}\n"
doc_content += "```\n\n"

doc_content += "### Pagination Format\n"
doc_content += "List endpoints that support pagination return:\n"
doc_content += "```json\n"
doc_content += "{\n"
doc_content += "  \"current_page\": 1,\n"
doc_content += "  \"data\": [ \* Array of resources *\ ],\n"
doc_content += "  \"first_page_url\": \"string\",\n"
doc_content += "  \"from\": 1,\n"
doc_content += "  \"last_page\": 1,\n"
doc_content += "  \"last_page_url\": \"string\",\n"
doc_content += "  \"next_page_url\": null,\n"
doc_content += "  \"path\": \"string\",\n"
doc_content += "  \"per_page\": 15,\n"
doc_content += "  \"prev_page_url\": null,\n"
doc_content += "  \"to\": 15,\n"
doc_content += "  \"total\": 15\n"
doc_content += "}\n"
doc_content += "```\n\n"

doc_content += "## Rate Limiting\n"
doc_content += "Certain endpoints are protected by rate limiting middleware:\n"
doc_content += "- `throttle:login` - Login attempts\n"
doc_content += "- `throttle:reset` - Password reset attempts\n"
doc_content += "- `throttle:60,1` - General API (60 requests per minute)\n"
doc_content += "- `throttle:100,1` - Public endpoints (100 requests per minute)\n\n"

doc_content += "## Security\n"
doc_content += "- All endpoints (except public ones) require `auth:api` middleware\n"
doc_content += "- Passwords are hashed using bcrypt\n"
doc_content += "- Tokens are managed via Laravel Passport\n"
doc_content += "- CORS headers are configured\n"
doc_content += "- Input validation is performed on all requests\n\n"

doc_content += "## Contact\n"
doc_content += "For API support, contact the development team.\n"

# Write documentation file
with open(r'C:\xampp\htdocs\unihub\unihub-API\docs\API_DOCUMENTATION.md', 'w', encoding='utf-8') as f:
    f.write(doc_content)

print(f"Generated API documentation with {len(active_routes)} routes")
print(f"Found {len(commented_routes)} commented routes")
print(r"Documentation saved to: C:\xampp\htdocs\unihub\unihub-API\docs\API_DOCUMENTATION.md")

