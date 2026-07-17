import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/features/academic/screens/schedule_screen.dart';
import 'package:superapp_namira_flutter/features/academic/screens/students_screen.dart';
import 'package:superapp_namira_flutter/features/attendance/screens/attendance_history_screen.dart';
import 'package:superapp_namira_flutter/features/attendance/screens/attendance_screen.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/features/auth/screens/login_screen.dart';
import 'package:superapp_namira_flutter/features/auth/screens/splash_screen.dart';
import 'package:superapp_namira_flutter/features/counseling/screens/counseling_screen.dart';
import 'package:superapp_namira_flutter/features/finance/screens/finance_screen.dart';
import 'package:superapp_namira_flutter/features/home/screens/home_screen.dart';
import 'package:superapp_namira_flutter/features/profile/screens/profile_screen.dart';
import 'package:superapp_namira_flutter/features/sarpar/screens/sarpar_screen.dart';
import 'package:superapp_namira_flutter/shared/widgets/main_shell.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isAuthenticated = authState.isAuthenticated;
      final isAuthRoute = state.matchedLocation == '/login' ||
          state.matchedLocation == '/';
      final isSplashRoute = state.matchedLocation == '/';

      if (isSplashRoute) return null;

      if (!isAuthenticated && !isAuthRoute) {
        return '/login';
      }

      if (isAuthenticated && state.matchedLocation == '/login') {
        return '/home';
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
        ],
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
