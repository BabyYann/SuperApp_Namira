class AppConstants {
  static const String appName = 'SuperApp Namira';
  static const String appVersion = '1.0.0';

  static const Duration apiTimeout = Duration(seconds: 10);

  static const int itemsPerPage = 20;

  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String activeUnitKey = 'active_unit_id';
  static const String activeRoleKey = 'active_role';

  static const List<String> allowedImageExtensions = [
    'jpg', 'jpeg', 'png', 'webp',
  ];
  static const int maxImageSizeMB = 5;

  static const double defaultPadding = 16;
  static const double defaultRadius = 10;
  static const double cardRadius = 12;
}

class ApiEndpoints {
  static const String login = '/login';
  static const String logout = '/logout';
  static const String register = '/register';
  static const String user = '/user';
  static const String profile = '/profile';
  static const String units = '/units';
  static const String switchUnit = '/switch-unit';
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';

  static const String dashboard = '/dashboard';

  static const String attendanceToday = '/attendance/today';
  static const String attendanceCheckIn = '/attendance/check-in';
  static const String attendanceCheckOut = '/attendance/check-out';
  static const String attendanceHistory = '/attendance/history';
  static const String attendanceLocations = '/attendance/locations';

  static const String academicYears = '/academic-years';
  static const String students = '/students';
  static const String teachers = '/teachers';
  static const String employees = '/employees';
  static const String classes = '/classes';
  static const String subjects = '/subjects';
  static const String attendance = '/attendance';
  static const String scores = '/scores';

  static const String financeAccounts = '/finance/accounts';
  static const String financeTransactions = '/finance/transactions';
  static const String financeReports = '/finance/reports';

  static const String counseling = '/counseling';
  static const String sarpar = '/sarpar';
  static const String lms = '/lms';

  static const String publicRelations = '/public-relations';
  static const String announcements = '/announcements';
  static const String gallery = '/gallery';

  // Phase 0 - New module endpoints
  static const String lmsClassrooms = '/lms/classrooms';
  static const String lmsMaterials = '/lms/materials';
  static const String lmsAssignments = '/lms/assignments';
  static const String lmsSubmissions = '/lms/submissions';
  static const String lmsGradebook = '/lms/gradebook';
  static const String lmsAnnouncements = '/lms/announcements';
  static const String lmsMyTasks = '/lms/my-tasks';

  static const String prNews = '/pr/news';
  static const String prEvents = '/pr/events';
  static const String prDestinations = '/pr/destinations';

  static const String studentDashboard = '/student/dashboard';
  static const String studentTasks = '/student/tasks';
  static const String studentPickup = '/student/pickup';
  static const String studentGrades = '/student/grades';
  static const String studentSchedule = '/student/schedule';
  static const String studentProfile = '/student/profile';

  static const String adminUsers = '/admin/users';
  static const String adminRoles = '/admin/roles';

  static const String employeeStaff = '/employee/staff';
  static const String employeeAttendance = '/employee/attendance';

  static const String notifications = '/notifications';
}
