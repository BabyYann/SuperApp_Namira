import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/utils/storage_utils.dart';
import 'package:superapp_namira_flutter/features/auth/data/auth_repository.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthState {
  final AuthStatus status;
  final Map<String, dynamic>? user;
  final List<dynamic>? units;
  final int? activeUnitId;
  final String? activeRole;
  final String? errorMessage;

  const AuthState({
    this.status = AuthStatus.initial,
    this.user,
    this.units,
    this.activeUnitId,
    this.activeRole,
    this.errorMessage,
  });

  AuthState copyWith({
    AuthStatus? status,
    Map<String, dynamic>? user,
    List<dynamic>? units,
    int? activeUnitId,
    String? activeRole,
    String? errorMessage,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      units: units ?? this.units,
      activeUnitId: activeUnitId ?? this.activeUnitId,
      activeRole: activeRole ?? this.activeRole,
      errorMessage: errorMessage,
    );
  }

  bool get isAuthenticated => status == AuthStatus.authenticated;
  bool get isLoading => status == AuthStatus.loading;

  String get userName => user?['name'] ?? '';
  String get userEmail => user?['email'] ?? '';
  String? get profilePhoto => user?['profile_photo_url'];

  bool get needsWorkspaceSelection {
    if (units == null || units!.isEmpty) return false;
    return activeUnitId == null || activeRole == null;
  }
  List<String> get roles {
    if (user == null) return [];
    final roles = user!['roles'];
    if (roles is List) {
      return roles.map((r) => r.toString()).toList();
    }
    return [];
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _repository;

  AuthNotifier(this._repository) : super(const AuthState()) {
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    debugPrint('[Auth] Checking auth status...');
    try {
      final hasToken = await StorageUtils.hasToken();
      debugPrint('[Auth] hasToken: $hasToken');

      if (!hasToken) {
        state = state.copyWith(status: AuthStatus.unauthenticated);
        return;
      }

      state = state.copyWith(status: AuthStatus.loading);
      final result = await _repository.getProfile();
      if (result.success && result.data != null) {
        final activeUnit = await StorageUtils.getActiveUnit();
        final profile = result.data!;
        var resolvedUnit = activeUnit;

        if (resolvedUnit == null) {
          final userUnits = profile['units'] as List<dynamic>?;
          if (userUnits != null && userUnits.isNotEmpty) {
            resolvedUnit = userUnits.first['id'] as int;
            await StorageUtils.saveActiveUnit(resolvedUnit);
          }
        }

        final activeRole = await StorageUtils.getActiveRole();

        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: result.data,
          units: profile['units'] as List<dynamic>?,
          activeUnitId: resolvedUnit,
          activeRole: activeRole,
        );
      } else {
        await StorageUtils.clearAuth();
        state = state.copyWith(status: AuthStatus.unauthenticated);
      }
    } catch (e) {
      debugPrint('[Auth] Check error: $e');
      await StorageUtils.clearAuth();
      state = state.copyWith(status: AuthStatus.unauthenticated);
    }
  }

  Future<bool> login(String email, String password) async {
    debugPrint('[Auth] Login starting: $email');
    state = state.copyWith(status: AuthStatus.loading);
    final result = await _repository.login(email: email, password: password);

    debugPrint('[Auth] Login result success: ${result.success}, message: ${result.message}');

    if (result.success && result.data != null) {
      final data = result.data!;
      final user = data['user'] as Map<String, dynamic>?;
      final units = data['units'] as List<dynamic>?;
      var activeUnit = await StorageUtils.getActiveUnit();

      if (activeUnit == null && units != null && units.isNotEmpty) {
        activeUnit = units.first['id'] as int;
        await StorageUtils.saveActiveUnit(activeUnit);
      }

      final activeRole = await StorageUtils.getActiveRole();

      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: user,
        units: units,
        activeUnitId: activeUnit,
        activeRole: activeRole,
      );
      return true;
    } else {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: result.message,
      );
      return false;
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }

  Future<void> switchUnit(int unitId) async {
    final result = await _repository.switchUnit(unitId);
    if (result.success) {
      await StorageUtils.saveActiveUnit(unitId);
      state = state.copyWith(activeUnitId: unitId, activeRole: null);
    }
  }

  Future<void> setActiveRole(String role) async {
    await StorageUtils.saveActiveRole(role);
    state = state.copyWith(activeRole: role);
  }

  Future<void> refreshProfile() async {
    final result = await _repository.getProfile();
    if (result.success && result.data != null) {
      state = state.copyWith(user: result.data);
    }
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final repository = ref.watch(authRepositoryProvider);
  return AuthNotifier(repository);
});
