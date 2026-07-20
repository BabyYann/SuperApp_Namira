import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const Color primary = Color(0xFF00685E);
  static const Color primaryContainer = Color(0xFF008377);
  static const Color onPrimary = Colors.white;
  static const Color onPrimaryContainer = Color(0xFFF4FFFB);

  static const Color secondary = Color(0xFF3A5F94);
  static const Color secondaryContainer = Color(0xFF9FC2FE);
  static const Color onSecondaryContainer = Color(0xFF294F83);

  static const Color tertiary = Color(0xFF9B4100);
  static const Color tertiaryContainer = Color(0xFFC25300);

  static const Color error = Color(0xFFBA1A1A);
  static const Color errorContainer = Color(0xFFFFDAD6);
  static const Color onErrorContainer = Color(0xFF93000A);

  static const Color success = Color(0xFF38A169);
  static const Color warning = Color(0xFFD69E2E);
  static const Color info = Color(0xFF3182CE);

  static const Color background = Color(0xFFF8FAFC);
  static const Color surface = Colors.white;
  static const Color surfaceContainerLow = Color(0xFFF3F2FF);
  static const Color surfaceContainer = Color(0xFFECECFF);
  static const Color surfaceVariant = Color(0xFFDDE1FF);

  static const Color textPrimary = Color(0xFF151A31);
  static const Color textSecondary = Color(0xFF718096);
  static const Color textOnSurfaceVariant = Color(0xFF3D4947);
  static const Color textHint = Color(0xFFA0AEC0);

  static const Color divider = Color(0xFFE2E8F0);
  static const Color border = Color(0xFF6D7A77);
  static const Color borderLight = Color(0xFFE2E8F0);
  static const Color outlineVariant = Color(0xFFBCC9C6);

  static const Color sidebarBg = Color(0xFF3A5F94);
  static const Color sidebarText = Color(0xFFE2E8F0);
  static const Color sidebarActive = Color(0xFF00685E);

  static const Color glassWhite = Color(0x80FFFFFF);
  static const Color glassWhiteHigh = Color(0xE6FFFFFF);
  static const Color glassBorder = Color(0x80FFFFFF);
}

class AppShadows {
  static List<BoxShadow> level1 = [
    BoxShadow(
      blurRadius: 4,
      offset: const Offset(0, 1),
      color: Colors.black.withAlpha(13),
    ),
  ];

  static List<BoxShadow> level2 = [
    BoxShadow(
      blurRadius: 8,
      offset: const Offset(0, 2),
      color: Colors.black.withAlpha(20),
    ),
  ];

  static List<BoxShadow> level3 = [
    BoxShadow(
      blurRadius: 15,
      offset: const Offset(0, 10),
      color: Colors.black.withAlpha(26),
    ),
  ];
}

class AppTheme {
  static ThemeData get light {
    final textTheme = GoogleFonts.plusJakartaSansTextTheme();

    return ThemeData(
      useMaterial3: true,
      colorScheme: const ColorScheme.light(
        primary: AppColors.primary,
        onPrimary: AppColors.onPrimary,
        primaryContainer: AppColors.primaryContainer,
        secondary: AppColors.secondary,
        secondaryContainer: AppColors.secondaryContainer,
        tertiary: AppColors.tertiary,
        tertiaryContainer: AppColors.tertiaryContainer,
        error: AppColors.error,
        errorContainer: AppColors.errorContainer,
        surface: AppColors.surface,
        outline: AppColors.border,
      ),
      scaffoldBackgroundColor: AppColors.background,
      textTheme: textTheme.copyWith(
        headlineLarge: textTheme.headlineLarge?.copyWith(
          color: AppColors.textPrimary,
          fontWeight: FontWeight.w700,
          letterSpacing: -0.02,
        ),
        headlineMedium: textTheme.headlineMedium?.copyWith(
          color: AppColors.textPrimary,
          fontWeight: FontWeight.w600,
        ),
        headlineSmall: textTheme.headlineSmall?.copyWith(
          color: AppColors.textPrimary,
          fontWeight: FontWeight.w600,
        ),
        titleLarge: textTheme.titleLarge?.copyWith(
          color: AppColors.textPrimary,
          fontWeight: FontWeight.w600,
        ),
        titleMedium: textTheme.titleMedium?.copyWith(
          color: AppColors.textPrimary,
          fontWeight: FontWeight.w600,
        ),
        bodyLarge: textTheme.bodyLarge?.copyWith(
          color: AppColors.textPrimary,
        ),
        bodyMedium: textTheme.bodyMedium?.copyWith(
          color: AppColors.textOnSurfaceVariant,
        ),
        labelLarge: textTheme.labelLarge?.copyWith(
          color: AppColors.onPrimary,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.1,
        ),
        labelMedium: textTheme.labelMedium?.copyWith(
          color: AppColors.textOnSurfaceVariant,
          fontWeight: FontWeight.w500,
          letterSpacing: 0.5,
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        centerTitle: false,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: GoogleFonts.plusJakartaSans(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: AppColors.onPrimary,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: const StadiumBorder(),
          elevation: 0,
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.1,
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.secondary,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 0,
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.textPrimary,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          side: const BorderSide(color: AppColors.border),
          shape: const StadiumBorder(),
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AppColors.outlineVariant),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
        hintStyle: GoogleFonts.plusJakartaSans(
          color: AppColors.textHint,
          fontSize: 14,
        ),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.outlineVariant),
        ),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        surfaceTintColor: Colors.transparent,
      ),
      chipTheme: ChipThemeData(
        shape: StadiumBorder(
          side: const BorderSide(color: AppColors.outlineVariant),
        ),
        labelStyle: GoogleFonts.plusJakartaSans(
          fontSize: 13,
          fontWeight: FontWeight.w500,
          color: AppColors.textOnSurfaceVariant,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        backgroundColor: AppColors.surfaceContainerLow,
        selectedColor: AppColors.primaryContainer,
        brightness: Brightness.light,
      ),
      dividerTheme: const DividerThemeData(
        color: AppColors.outlineVariant,
        thickness: 1,
        space: 1,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }

  static ThemeData get dark {
    final textTheme = GoogleFonts.plusJakartaSansTextTheme(
      ThemeData(brightness: Brightness.dark).textTheme,
    );

    const bg = Color(0xFF121212);
    const surface = Color(0xFF1E1E1E);
    const surfaceVariant = Color(0xFF2C2C2C);
    const textPrimary = Color(0xFFE2E8F0);
    const textSecondary = Color(0xFFA0AEC0);
    const border = Color(0xFF3A3A3A);
    const outlineVariant = Color(0xFF444444);

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: ColorScheme.dark(
        primary: const Color(0xFF67D9C9),
        onPrimary: const Color(0xFF003830),
        primaryContainer: const Color(0xFF005048),
        secondary: const Color(0xFFA7C8FF),
        onSecondary: const Color(0xFF002C5B),
        secondaryContainer: const Color(0xFF1F4578),
        tertiary: const Color(0xFFFFB690),
        onTertiary: const Color(0xFF4E2500),
        error: const Color(0xFFFFB4AB),
        onError: const Color(0xFF690005),
        surface: surface,
        outline: border,
      ),
      scaffoldBackgroundColor: bg,
      textTheme: textTheme.copyWith(
        headlineLarge: textTheme.headlineLarge?.copyWith(
          color: textPrimary,
          fontWeight: FontWeight.w700,
          letterSpacing: -0.02,
        ),
        headlineMedium: textTheme.headlineMedium?.copyWith(
          color: textPrimary,
          fontWeight: FontWeight.w600,
        ),
        headlineSmall: textTheme.headlineSmall?.copyWith(
          color: textPrimary,
          fontWeight: FontWeight.w600,
        ),
        titleLarge: textTheme.titleLarge?.copyWith(
          color: textPrimary,
          fontWeight: FontWeight.w600,
        ),
        titleMedium: textTheme.titleMedium?.copyWith(
          color: textPrimary,
          fontWeight: FontWeight.w600,
        ),
        bodyLarge: textTheme.bodyLarge?.copyWith(
          color: textPrimary,
        ),
        bodyMedium: textTheme.bodyMedium?.copyWith(
          color: textSecondary,
        ),
        labelLarge: textTheme.labelLarge?.copyWith(
          color: Colors.white,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.1,
        ),
        labelMedium: textTheme.labelMedium?.copyWith(
          color: textSecondary,
          fontWeight: FontWeight.w500,
          letterSpacing: 0.5,
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: surface,
        foregroundColor: textPrimary,
        elevation: 0,
        centerTitle: false,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: GoogleFonts.plusJakartaSans(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: textPrimary,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF67D9C9),
          foregroundColor: const Color(0xFF003830),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: const StadiumBorder(),
          elevation: 0,
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.1,
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: const Color(0xFFA7C8FF),
          foregroundColor: const Color(0xFF002C5B),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 0,
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: textPrimary,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          side: const BorderSide(color: border),
          shape: const StadiumBorder(),
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surfaceVariant,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: outlineVariant),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFF67D9C9), width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFFFFB4AB)),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
        hintStyle: GoogleFonts.plusJakartaSans(
          color: textSecondary,
          fontSize: 14,
        ),
      ),
      cardTheme: CardThemeData(
        color: surface,
        elevation: 0,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: outlineVariant),
        ),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        surfaceTintColor: Colors.transparent,
      ),
      chipTheme: ChipThemeData(
        shape: StadiumBorder(
          side: BorderSide(color: outlineVariant),
        ),
        labelStyle: GoogleFonts.plusJakartaSans(
          fontSize: 13,
          fontWeight: FontWeight.w500,
          color: textSecondary,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        backgroundColor: surfaceVariant,
        selectedColor: const Color(0xFF005048),
        brightness: Brightness.dark,
      ),
      dividerTheme: DividerThemeData(
        color: outlineVariant,
        thickness: 1,
        space: 1,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }
}
