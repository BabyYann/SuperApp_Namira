import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/app.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await dotenv.load(fileName: '.env');
  debugPrint('[Main] Starting SuperApp Namira...');

  runApp(
    const ProviderScope(
      child: SuperAppNamira(),
    ),
  );
}
