import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/features/academic/screens/schedule_screen.dart';
import 'package:superapp_namira_flutter/features/academic/screens/students_screen.dart';
import 'package:superapp_namira_flutter/features/attendance/screens/attendance_history_screen.dart';
import 'package:superapp_namira_flutter/features/attendance/screens/attendance_screen.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/features/auth/screens/login_screen.dart';
import 'package:superapp_namira_flutter/features/auth/screens/role_selection_screen.dart';
import 'package:superapp_namira_flutter/features/auth/screens/splash_screen.dart';
import 'package:superapp_namira_flutter/features/counseling/screens/counseling_screen.dart';
import 'package:superapp_namira_flutter/features/finance/screens/finance_screen.dart';
import 'package:superapp_namira_flutter/features/home/screens/home_screen.dart';
import 'package:superapp_namira_flutter/features/lms/screens/lms_screen.dart';
import 'package:superapp_namira_flutter/features/notifications/screens/notifications_screen.dart';
import 'package:superapp_namira_flutter/features/profile/screens/profile_screen.dart';
import 'package:superapp_namira_flutter/features/public_relations/screens/public_relations_screen.dart';
import 'package:superapp_namira_flutter/features/sarpar/screens/sarpar_screen.dart';
import 'package:superapp_namira_flutter/features/student_portal/screens/student_portal_screen.dart';
import 'package:superapp_namira_flutter/shared/widgets/main_shell.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isAuthenticated = authState.isAuthenticated;
      final location = state.matchedLocation;
      final isAuthRoute = location == '/login' || location == '/';
      final isSplashRoute = location == '/';
      final isRoleSelection = location == '/role-selection';

      if (isSplashRoute) return null;

      if (!isAuthenticated && !isAuthRoute) {
        return '/login';
      }

      if (isAuthenticated) {
        if (isAuthRoute) {
          return authState.needsWorkspaceSelection
              ? '/role-selection'
              : '/home';
        }
        if (!isRoleSelection && authState.needsWorkspaceSelection) {
          return '/role-selection';
        }
        if (isRoleSelection && !authState.needsWorkspaceSelection) {
          return '/home';
        }
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/role-selection',
        name: 'role-selection',
        builder: (context, state) => const RoleSelectionScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          GoRoute(
            path: '/home',
            name: 'home',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: HomeScreen(),
            ),
          ),
          GoRoute(
            path: '/schedules',
            name: 'schedules',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: ScheduleScreen(),
            ),
          ),
          GoRoute(
            path: '/academic',
            name: 'academic',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: StudentsScreen(),
            ),
          ),
          GoRoute(
            path: '/profile',
            name: 'profile',
            pageBuilder: (context, state) => const NoTransitionPage(
              child: ProfileScreen(),
            ),
          ),
          GoRoute(
            path: '/attendance',
            name: 'attendance',
            builder: (context, state) => const AttendanceScreen(),
          ),
          GoRoute(
            path: '/attendance/history',
            name: 'attendance-history',
            builder: (context, state) => const AttendanceHistoryScreen(),
          ),
          GoRoute(
            path: '/finance',
            name: 'finance',
            builder: (context, state) => const FinanceScreen(),
          ),
          GoRoute(
            path: '/counseling',
            name: 'counseling',
            builder: (context, state) => const CounselingScreen(),
          ),
          GoRoute(
            path: '/sarpar',
            name: 'sarpar',
            builder: (context, state) => const SarparScreen(),
          ),
          GoRoute(
            path: '/lms',
            name: 'lms',
            builder: (context, state) => const LmsScreen(),
          ),
          GoRoute(
            path: '/lms/my-tasks',
            name: 'lms-my-tasks',
            builder: (context, state) => const LmsMyTasksScreen(),
          ),
          GoRoute(
            path: '/public-relations',
            name: 'public-relations',
            builder: (context, state) => const PublicRelationsScreen(),
          ),
          GoRoute(
            path: '/student',
            name: 'student',
            builder: (context, state) => const StudentPortalScreen(),
          ),
          GoRoute(
            path: '/notifications',
            name: 'notifications',
            builder: (context, state) => const NotificationsScreen(),
          ),
        ],
      ),
      GoRoute(
        path: '/lms/classrooms/:id',
        name: 'lms-classroom-detail',
        builder: (context, state) => LmsClassroomDetailScreen(
          classroomId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/pr/news/:id',
        name: 'pr-news-detail',
        builder: (context, state) => PrNewsDetailScreen(
          id: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/pr/events/:id',
        name: 'pr-event-detail',
        builder: (context, state) => PrEventDetailScreen(
          id: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/pr/destinations/:id',
        name: 'pr-destination-detail',
        builder: (context, state) => PrDestinationDetailScreen(
          id: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/student/pickup',
        name: 'student-pickup',
        builder: (context, state) => const StudentPickupScreen(),
      ),
    ],
    errorBuilder: (context, state) => Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 48, color: Colors.red),
            const SizedBox(height: 16),
            Text('Halaman tidak ditemukan: ${state.matchedLocation}'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => context.go('/home'),
              child: const Text('Kembali ke Beranda'),
            ),
          ],
        ),
      ),
    ),
  );
});
