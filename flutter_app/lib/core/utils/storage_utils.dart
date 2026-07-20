import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class StorageUtils {
  static const _storage = FlutterSecureStorage();
  static const _timeout = Duration(seconds: 3);

  static Future<T?> _withTimeout<T>(Future<T> Function() fn, String label) async {
    try {
      return await fn().timeout(_timeout);
    } catch (e) {
      debugPrint('[Storage] $label failed: $e');
      return null;
    }
  }

  static Future<void> saveToken(String token) async {
    await _withTimeout(
      () => _storage.write(key: AppConstants.tokenKey, value: token),
      'saveToken',
    );
  }

  static Future<String?> getToken() async {
    return _withTimeout<String?>(
      () => _storage.read(key: AppConstants.tokenKey),
      'getToken',
    );
  }

  static Future<void> saveUser(Map<String, dynamic> user) async {
    await _withTimeout(
      () => _storage.write(key: AppConstants.userKey, value: jsonEncode(user)),
      'saveUser',
    );
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final data = await _withTimeout(
      () => _storage.read(key: AppConstants.userKey),
      'getUser',
    );
    if (data == null) return null;
    return jsonDecode(data) as Map<String, dynamic>;
  }

  static Future<void> saveActiveUnit(int unitId) async {
    await _withTimeout(
      () => _storage.write(key: AppConstants.activeUnitKey, value: unitId.toString()),
      'saveActiveUnit',
    );
  }

  static Future<int?> getActiveUnit() async {
    final id = await _withTimeout(
      () => _storage.read(key: AppConstants.activeUnitKey),
      'getActiveUnit',
    );
    if (id == null) return null;
    return int.tryParse(id);
  }

  static Future<void> saveActiveRole(String role) async {
    await _withTimeout(
      () => _storage.write(key: AppConstants.activeRoleKey, value: role),
      'saveActiveRole',
    );
  }

  static Future<String?> getActiveRole() async {
    return _withTimeout<String?>(
      () => _storage.read(key: AppConstants.activeRoleKey),
      'getActiveRole',
    );
  }

  static Future<void> clearAuth() async {
    await _withTimeout(() async {
      await _storage.delete(key: AppConstants.tokenKey);
      await _storage.delete(key: AppConstants.userKey);
      await _storage.delete(key: AppConstants.activeUnitKey);
    }, 'clearAuth');
  }

  static Future<void> clearAll() async {
    await _withTimeout(() => _storage.deleteAll(), 'clearAll');
  }

  static Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }
}
