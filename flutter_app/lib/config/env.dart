import 'package:flutter_dotenv/flutter_dotenv.dart';

class Env {
  static String get apiBaseUrl =>
      dotenv.env['API_BASE_URL'] ?? 'http://10.0.2.2:8000/api';

  static String get apiTimeout =>
      dotenv.env['API_TIMEOUT'] ?? '30000';

  static String get appName =>
      dotenv.env['APP_NAME'] ?? 'SuperApp Namira';
}
