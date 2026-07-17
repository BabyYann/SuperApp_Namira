import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/app.dart';

void main() {
  testWidgets('App renders splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: SuperAppNamira(),
      ),
    );
    await tester.pump();

    expect(find.text('SuperApp Namira'), findsOneWidget);
  });
}
