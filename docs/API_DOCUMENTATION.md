# UniHub API Documentation

**Generated on:** 2026-08-25 00:44:10
**Base URL:** `pi1`
**Authentication:** Bearer Token (Laravel Passport)

## Table of Contents
1. [Authentication](#authentication)
2. [User Management](#user-management)
3. [College Management](#college-management)
4. [Department Management](#department-management)
5. [Program Management](#program-management)
6. [Level Management](#level-management)
7. [Semester Management](#semester-management)
8. [Course Management](#course-management)
9. [Building Management](#building-management)
10. [Classroom Management](#classroom-management)
11. [Block Management](#block-management)
12. [Academic Title Management](#academic-title-management)
13. [Lecturer Management](#lecturer-management)
14. [Student Management](#student-management)
15. [Student Group Management](#student-group-management)
16. [Attendance Management](#attendance-management)
17. [Lecturer Attendance Management](#lecturer-attendance-management)
18. [Timetable Management](#timetable-management)
19. [Lecture Session Management](#lecture-session-management)
20. [QR Code Management](#qr-code-management)
21. [Lookup Tables](#lookup-tables)
22. [Dashboard & Reports](#dashboard--reports)
23. [Financial Management](#financial-management)
24. [Quality Assurance](#quality-assurance)
25. [Sync Management](#sync-management)
26. [Commented/Inactive Routes](#commented-inactive-routes)

---

## Authentication

### Login
- **URL:** `pi1uth\login`
- **Method:** `POST`
- **Description:** Authenticate user and receive access token
- **Middleware:** `throttle:login`
- **Request Body:**
  ```json
  {
    "email": "string (required)",
    "password": "string (required)",
    "mac_address": "string (required, max:100)",
    "device_name": "string (required, max:100)",
    "os_type": "string (required, max:50)"
  }
  ```
- **Success Response:**
  ```json
  {
    "access_token": "string",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "user_id": "integer",
      "full_name": "string",
      "email": "string",
      "phone": "string",
      "academic_number": "string",
      "gender": "integer (1=male, 2=female)",
      "userType": {
        "user_type_id": "integer",
        "user_type_name": "string",
        "user_type_code": "string (student|lecturer|admin)"
      },
      "college": {
        "college_id": "integer",
        "college_name": "string"
      }
    }
  }
  ```
- **Error Responses:**
  - `401`: Invalid credentials
  - `422`: Validation error
  - `429`: Too Many Requests (throttle)

### Verify OTP
- **URL:** `pi1utherify-otp`
- **Method:** `POST`
- **Description:** Verify OTP for new device login
- **Request Body:**
  ```json
  {
    "verification_id": "integer (required)",
    "otp_code": "string (required, exactly 6 digits)"
  }
  ```
- **Success Response:**
  ```json
  {
    "access_token": "string",
    "token_type": "Bearer",
    "user": { /* User object */ }
  }
  ```

### Forgot Password
- **URL:** `pi1uthorgot-password`
- **Method:** `POST`
- **Description:** Request password reset link
- **Request Body:**
  ```json
  {
    "email": "string (required)"
  }
  ```

### Reset Password
- **URL:** `pi1uth
eset-password`
- **Method:** `POST`
- **Description:** Reset password with token
- **Middleware:** `throttle:reset`
- **Request Body:**
  ```json
  {
    "email": "string (required)",
    "password": "string (required|confirmed|min:8)",
    "password_confirmation": "string (required)",
    "token": "string (required)"
  }
  ```

### Get Authenticated User
- **URL:** `pi1uth\me`
- **Method:** `GET`
- **Description:** Get currently authenticated user profile
- **Middleware:** `auth:api`
- **Success Response:** User object (same as login response)

### Logout
- **URL:** `pi1uth\logout`
- **Method:** `POST`
- **Description:** Logout user and revoke token
- **Middleware:** `auth:api`
- **Success Response:**
  ```json
  {
    "message": "تم تسجيل الخروج بنجاح."
  }
  ```

### Change Password
- **URL:** `pi1uth\change-password`
- **Method:** `POST`
- **Description:** Change password for authenticated user
- **Middleware:** `auth:api`
- **Request Body:**
  ```json
  {
    "password": "string (required|confirmed|min:8)"
  }
  ```
- **Success Response:**
  ```json
  {
    "status": true,
    "message": "تم تغيير كلمة المرور بنجاح。"
  }
  ```

### Refresh Token
- **URL:** `pi1uth
efresh`
- **Method:** `POST`
- **Description:** Refresh access token using refresh token
- **Request Body:**
  ```json
  {
    "refresh_token": "string (required)"
  }
  ```
- **Success Response:** New access token object

---

## User Management

### Users Controller

### AcademicTitlesController

#### DELETE Operations
- **DELETE** `academic-titles/{academic-title}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `academic-titles`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `academic-titles/{academic-title}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `academic-titles`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `academic-titles/{academic-title}`
  - Action: `update`
  - Description: Update lecture attachment

### AssessmentMethodController

#### DELETE Operations
- **DELETE** `assessment-methods/{assessment-method}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `assessment-methods`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `assessment-methods/{assessment-method}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `assessment-methods`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `assessment-methods/{assessment-method}`
  - Action: `update`
  - Description: Update lecture attachment

### BlockController

#### DELETE Operations
- **DELETE** `blocks/{block}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `blocks`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `blocks/{block}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `blocks`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `blocks/{block}`
  - Action: `update`
  - Description: Update lecture attachment

### BuildingsController

#### DELETE Operations
- **DELETE** `buildings/{building}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `buildings`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `buildings/{building}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `buildings`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `buildings/{building}`
  - Action: `update`
  - Description: Update lecture attachment

### ClassroomsController

#### DELETE Operations
- **DELETE** `classrooms/{classroom}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `classrooms`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `classrooms/{classroom}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `classrooms`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `classrooms/{classroom}`
  - Action: `update`
  - Description: Update lecture attachment

### CollegesController

#### DELETE Operations
- **DELETE** `colleges/{college}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `colleges`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `colleges/{college}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `colleges`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `colleges/{college}`
  - Action: `update`
  - Description: Update lecture attachment

### CourseAssessmentController

#### DELETE Operations
- **DELETE** `assessments/{assessment}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `assessments`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `assessments/{assessment}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `assessments`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `assessments/{assessment}`
  - Action: `update`
  - Description: Update lecture attachment

### CourseAssignmentController

#### DELETE Operations
- **DELETE** `assignments/{assignment}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `assignments`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `assignments/{assignment}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `assignments`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `assignments/{assignment}`
  - Action: `update`
  - Description: Update lecture attachment

### CourseLearningOutcomeController

#### DELETE Operations
- **DELETE** `learning-outcomes/{learning-outcome}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `learning-outcomes`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `learning-outcomes/{learning-outcome}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `learning-outcomes`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `learning-outcomes/{learning-outcome}`
  - Action: `update`
  - Description: Update lecture attachment

### CoursePolicyController

#### DELETE Operations
- **DELETE** `policies/{policie}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `policies`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `policies/{policie}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `policies`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `policies/{policie}`
  - Action: `update`
  - Description: Update lecture attachment

### CourseReferenceController

#### DELETE Operations
- **DELETE** `references/{reference}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `references`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `references/{reference}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `references`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `references/{reference}`
  - Action: `update`
  - Description: Update lecture attachment

### CourseTopicController

#### DELETE Operations
- **DELETE** `topics/{topic}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `topics`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `topics/{topic}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `topics`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `topics/{topic}`
  - Action: `update`
  - Description: Update lecture attachment

### CoursesController

#### DELETE Operations
- **DELETE** `courses/{course}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `courses`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `courses/{course}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `courses`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `courses/{course}`
  - Action: `update`
  - Description: Update lecture attachment

### DaysController

#### DELETE Operations
- **DELETE** `days/{day}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `days`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `days/{day}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `days`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `days/{day}`
  - Action: `update`
  - Description: Update lecture attachment

### DepartmentsController

#### DELETE Operations
- **DELETE** `departments/{department}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `departments`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `departments/{department}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `departments`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `departments/{department}`
  - Action: `update`
  - Description: Update lecture attachment

### IpRestrictionController

#### DELETE Operations
- **DELETE** `ip-restrictions/{ip-restriction}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `ip-restrictions`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `ip-restrictions/{ip-restriction}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `ip-restrictions`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `ip-restrictions/{ip-restriction}`
  - Action: `update`
  - Description: Update lecture attachment

### LectureSessionsController

#### DELETE Operations
- **DELETE** `lecture-sessions/{lecture-session}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `lecture-sessions`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `lecture-sessions/{lecture-session}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `lecture-sessions`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `lecture-sessions/{lecture-session}`
  - Action: `update`
  - Description: Update lecture attachment

### LecturerAttendanceController

#### DELETE Operations
- **DELETE** `lecturer-attendance/{lecturer-attendance}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `lecturer-attendance`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `lecturer-attendance/{lecturer-attendance}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `lecturer-attendance`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `lecturer-attendance/{lecturer-attendance}`
  - Action: `update`
  - Description: Update lecture attachment

### LecturersController

#### DELETE Operations
- **DELETE** `lecturers/{lecturer}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `lecturers`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `lecturers/{lecturer}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `lecturers`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `lecturers/{lecturer}`
  - Action: `update`
  - Description: Update lecture attachment

### LevelsController

#### DELETE Operations
- **DELETE** `levels/{level}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `levels`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `levels/{level}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `levels`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `levels/{level}`
  - Action: `update`
  - Description: Update lecture attachment

### PeriodsController

#### DELETE Operations
- **DELETE** `periods/{period}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `periods`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `periods/{period}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `periods`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `periods/{period}`
  - Action: `update`
  - Description: Update lecture attachment

### ProgramsController

#### DELETE Operations
- **DELETE** `programs/{program}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `programs`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `programs/{program}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `programs`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `programs/{program}`
  - Action: `update`
  - Description: Update lecture attachment

### SemestersController

#### DELETE Operations
- **DELETE** `semesters/{semester}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `semesters`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `semesters/{semester}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `semesters`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `semesters/{semester}`
  - Action: `update`
  - Description: Update lecture attachment

### StudentAttendanceController

#### DELETE Operations
- **DELETE** `student-attendance/{student-attendance}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `student-attendance`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `student-attendance/{student-attendance}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `student-attendance`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `student-attendance/{student-attendance}`
  - Action: `update`
  - Description: Update lecture attachment

### StudentGroupsController

#### DELETE Operations
- **DELETE** `student-groups/{student-group}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `student-groups`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `student-groups/{student-group}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `student-groups`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `student-groups/{student-group}`
  - Action: `update`
  - Description: Update lecture attachment

### StudentsController

#### DELETE Operations
- **DELETE** `students/{student}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `students`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `students/{student}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `students`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `students/{student}`
  - Action: `update`
  - Description: Update lecture attachment

### TeachingStrategyController

#### DELETE Operations
- **DELETE** `teaching-strategies/{teaching-strategie}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `teaching-strategies`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `teaching-strategies/{teaching-strategie}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `teaching-strategies`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `teaching-strategies/{teaching-strategie}`
  - Action: `update`
  - Description: Update lecture attachment

### TimetableController

#### DELETE Operations
- **DELETE** `timetable/{timetable}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `timetable`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `timetable/{timetable}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `timetable`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `timetable/{timetable}`
  - Action: `update`
  - Description: Update lecture attachment

### TopicQuestionController

#### DELETE Operations
- **DELETE** `questions/{question}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `questions`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `questions/{question}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `questions`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `questions/{question}`
  - Action: `update`
  - Description: Update lecture attachment

### UserTypeController

#### DELETE Operations
- **DELETE** `user-types/{user-type}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `user-types`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `user-types/{user-type}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `user-types`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `user-types/{user-type}`
  - Action: `update`
  - Description: Update lecture attachment

### UsersController

#### DELETE Operations
- **DELETE** `users/{user}`
  - Action: `destroy`
  - Description: Destroy lecture attachment

#### GET Operations
- **GET** `users`
  - Action: `index`
  - Description: Get university comprehensive report

- **GET** `users/{user}`
  - Action: `show`
  - Description: Show QA form

#### POST Operations
- **POST** `users`
  - Action: `store`
  - Description: Store lecture attachment

#### PUT/PATCH Operations
- **PUT/PATCH** `users/{user}`
  - Action: `update`
  - Description: Update lecture attachment

---

## Commented/Inactive Routes

The following routes are currently commented out in the codebase and may be activated in the future:

- Line 227: `// Route::get('lecturer/schedule', [ScheduleController::class, 'getSchedule']);`
- Line 665: `// Route::middleware(['auth:api', 'throttle:100,1'])->group(function () {`
- Line 670: `//     Route::prefix('programs/{program_id}')->group(function () {`
- Line 671: `//         Route::get('learning-outcomes', [ProgramLearningOutcomeController::class, 'index'])`
- Line 674: `//         Route::get('learning-outcomes/{plo_id}', [ProgramLearningOutcomeController::class, 'show'])`
- Line 678: `//         Route::get('learning-outcomes/stats', [ProgramLearningOutcomeController::class, 'stats'])`
- Line 681: `//         Route::get('learning-outcomes/domain/{domain}', [ProgramLearningOutcomeController::class, 'byDomain'])`
- Line 689: `//     Route::prefix('courses/{course_id}')->group(function () {`
- Line 691: `//         Route::get('description', [CourseDescriptionController::class, 'show'])`
- Line 695: `//         Route::get('learning-outcomes', [CourseLearningOutcomeController::class, 'index'])`
- Line 698: `//         Route::get('learning-outcomes/{clo_id}', [CourseLearningOutcomeController::class, 'show'])`
- Line 702: `//         Route::get('learning-outcomes/domain/{domain}', [CourseLearningOutcomeController::class, 'byDomain'])`
- Line 707: `//         Route::get('topics', [CourseTopicController::class, 'index'])`
- Line 710: `//         Route::get('topics/{topic_id}', [CourseTopicController::class, 'show'])`
- Line 714: `//         Route::get('topics/by-part/{part}', [CourseTopicController::class, 'byPart'])`
- Line 719: `//         Route::get('assignments', [CourseAssignmentController::class, 'index'])`
- Line 722: `//         Route::get('assignments/{assignment_id}', [CourseAssignmentController::class, 'show'])`
- Line 726: `//         Route::get('assignments/by-part/{part}', [CourseAssignmentController::class, 'byPart'])`
- Line 731: `//         Route::get('assessments', [CourseAssessmentController::class, 'index'])`
- Line 734: `//         Route::get('assessments/{assessment_id}', [CourseAssessmentController::class, 'show'])`

... and 24 more commented routes.


---

## Response Formats

### Success Responses
- **200 OK**: Request successful
- **201 Created**: Resource created successfully
- **202 Accepted**: Request accepted for processing

### Error Responses
- **400 Bad Request**: Invalid request syntax or invalid request message framing
- **401 Unauthorized**: Authentication required or failed
- **403 Forbidden**: Authenticated user does not have permission
- **404 Not Found**: Resource not found
- **405 Method Not Allowed**: HTTP method not supported for this resource
- **409 Conflict**: Request conflicts with current state of server
- **422 Unprocessable Entity**: Validation error
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Unexpected server error
- **501 Not Implemented**: Server does not support functionality
- **503 Service Unavailable**: Server temporarily unavailable

### Error Response Format
```json
{
  "message": "Error description",
  "status_code": 400,
  "errors": { /* Validation errors if applicable */ }
}
```

### Pagination Format
List endpoints that support pagination return:
```json
{
  "current_page": 1,
  "data": [ /* Array of resources */ ],
  "first_page_url": "string",
  "from": 1,
  "last_page": 1,
  "last_page_url": "string",
  "next_page_url": null,
  "path": "string",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 15
}
```

## Rate Limiting
Certain endpoints are protected by rate limiting middleware:
- `throttle:login` - Login attempts
- `throttle:reset` - Password reset attempts
- `throttle:60,1` - General API (60 requests per minute)
- `throttle:100,1` - Public endpoints (100 requests per minute)

## Security
- All endpoints (except public ones) require `auth:api` middleware
- Passwords are hashed using bcrypt
- Tokens are managed via Laravel Passport
- CORS headers are configured
- Input validation is performed on all requests

## Contact
For API support, contact the development team.
