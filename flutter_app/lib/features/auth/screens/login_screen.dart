import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _rememberMe = true;
  late AnimationController _animController;
  late Animation<double> _fadeAnim;
  late Animation<Offset> _slideAnim;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );
    _fadeAnim = CurvedAnimation(parent: _animController, curve: Curves.ease);
    _slideAnim = Tween<Offset>(
      begin: const Offset(0, 0.025),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _animController, curve: Curves.ease));
    _animController.forward();
  }

  @override
  void dispose() {
    _animController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    final success = await ref.read(authProvider.notifier).login(
          _emailController.text.trim(),
          _passwordController.text,
        );

    if (success && mounted) {
      context.go('/home');
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (previous, next) {
      if (next.status == AuthStatus.error && next.errorMessage != null) {
        ScaffoldMessenger.of(context).removeCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.errorMessage!),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        );
      }
    });

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Stack(
        children: [
          const _GeometricPattern(),
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 32),
                child: FadeTransition(
                  opacity: _fadeAnim,
                  child: SlideTransition(
                    position: _slideAnim,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const SizedBox(height: 16),
                        _buildHeader(),
                        const SizedBox(height: 32),
                        _buildCard(authState),
                        const SizedBox(height: 32),
                        _buildFooter(),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      children: [
        Container(
          width: 96,
          height: 96,
          alignment: Alignment.center,
          child: Text(
            'N',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 48,
              fontWeight: FontWeight.w800,
              color: AppColors.primary,
              height: 1,
            ),
          ),
        ),
        const SizedBox(height: 24),
        Text(
          'Namira',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 32,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
            letterSpacing: -0.02,
            height: 1.25,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'Selamat Datang',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w400,
            color: AppColors.textOnSurfaceVariant,
            height: 1.5,
          ),
        ),
      ],
    );
  }

  Widget _buildCard(AuthState authState) {
    return Container(
      width: double.infinity,
      constraints: const BoxConstraints(maxWidth: 480),
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppColors.outlineVariant.withAlpha(77),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(13),
            blurRadius: 3,
            offset: const Offset(0, 1),
          ),
          BoxShadow(
            color: Colors.black.withAlpha(5),
            blurRadius: 2,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildLabel('Email'),
            const SizedBox(height: 4),
            _buildEmailField(),
            const SizedBox(height: 24),
            _buildLabel('Kata Sandi'),
            const SizedBox(height: 4),
            _buildPasswordField(),
            const SizedBox(height: 16),
            _buildRememberRow(),
            const SizedBox(height: 24),
            _buildLoginButton(authState),
            const SizedBox(height: 32),
            _buildDivider(),
            const SizedBox(height: 32),
            _buildGoogleButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Text(
      text,
      style: GoogleFonts.plusJakartaSans(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: AppColors.textOnSurfaceVariant,
        letterSpacing: 0.1,
        height: 1.43,
      ),
    );
  }

  Widget _buildEmailField() {
    return SizedBox(
      height: 56,
      child: TextFormField(
        controller: _emailController,
        keyboardType: TextInputType.emailAddress,
        textInputAction: TextInputAction.next,
        style: GoogleFonts.plusJakartaSans(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: AppColors.textPrimary,
          height: 1.5,
        ),
        decoration: InputDecoration(
          hintText: 'nama@email.com',
          hintStyle: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w400,
            color: AppColors.border,
            height: 1.5,
          ),
          prefixIcon: const Icon(Icons.email_outlined, size: 20),
          prefixIconColor: AppColors.textOnSurfaceVariant,
          filled: true,
          fillColor: AppColors.surface,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.primary, width: 2),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.error),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
        ),
        validator: (value) {
          if (value == null || value.isEmpty) return 'Email harus diisi';
          if (!value.contains('@')) return 'Email tidak valid';
          return null;
        },
      ),
    );
  }

  Widget _buildPasswordField() {
    return SizedBox(
      height: 56,
      child: TextFormField(
        controller: _passwordController,
        obscureText: _obscurePassword,
        textInputAction: TextInputAction.done,
        onFieldSubmitted: (_) => _handleLogin(),
        style: GoogleFonts.plusJakartaSans(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: AppColors.textPrimary,
          height: 1.5,
        ),
        decoration: InputDecoration(
          hintText: 'Masukkan kata sandi',
          hintStyle: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w400,
            color: AppColors.border,
            height: 1.5,
          ),
          prefixIcon: const Icon(Icons.lock_outline, size: 20),
          prefixIconColor: AppColors.textOnSurfaceVariant,
          suffixIcon: IconButton(
            icon: Icon(
              _obscurePassword
                  ? Icons.visibility_off_outlined
                  : Icons.visibility_outlined,
              size: 20,
            ),
            onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
          ),
          suffixIconColor: AppColors.textOnSurfaceVariant,
          filled: true,
          fillColor: AppColors.surface,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.border),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.primary, width: 2),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppColors.error),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
        ),
        validator: (value) {
          if (value == null || value.isEmpty) return 'Password harus diisi';
          return null;
        },
      ),
    );
  }

  Widget _buildRememberRow() {
    return Row(
      children: [
        GestureDetector(
          onTap: () => setState(() => _rememberMe = !_rememberMe),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  color: _rememberMe ? AppColors.primary : Colors.transparent,
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(
                    color: _rememberMe ? AppColors.primary : AppColors.border,
                    width: 1.5,
                  ),
                ),
                child: _rememberMe
                    ? const Icon(Icons.check, size: 14, color: Colors.white)
                    : null,
              ),
              const SizedBox(width: 8),
              Text(
                'Ingat Saya',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textOnSurfaceVariant,
                  letterSpacing: 0.1,
                ),
              ),
            ],
          ),
        ),
        const Spacer(),
        TextButton(
          onPressed: () => context.push('/forgot-password'),
          style: TextButton.styleFrom(
            padding: EdgeInsets.zero,
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: Text(
            'Lupa Kata Sandi?',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.primary,
              letterSpacing: 0.1,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildLoginButton(AuthState authState) {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: ElevatedButton(
        onPressed: authState.isLoading ? null : _handleLogin,
        style: ElevatedButton.styleFrom(
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.1,
          ),
          elevation: 0,
          shadowColor: Colors.transparent,
        ),
        child: authState.isLoading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : const Text('Masuk'),
      ),
    );
  }

  Widget _buildDivider() {
    return Row(
      children: [
        Expanded(child: Container(height: 1, color: AppColors.outlineVariant)),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Text(
            'atau',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: AppColors.border,
              letterSpacing: 0.5,
            ),
          ),
        ),
        Expanded(child: Container(height: 1, color: AppColors.outlineVariant)),
      ],
    );
  }

  Widget _buildGoogleButton() {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: OutlinedButton.icon(
        onPressed: () {},
        icon: const SizedBox(
          width: 18,
          height: 18,
          child: Center(
            child: Text('G', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF4285F4))),
          ),
        ),
        label: const Text('Masuk dengan Google'),
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.textPrimary,
          side: const BorderSide(color: AppColors.border),
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.1,
          ),
          shape: const StadiumBorder(),
        ),
      ),
    );
  }

  Widget _buildFooter() {
    return Column(
      children: [
        RichText(
          textAlign: TextAlign.center,
          text: TextSpan(
            style: GoogleFonts.plusJakartaSans(
              fontSize: 14,
              fontWeight: FontWeight.w400,
              color: AppColors.textOnSurfaceVariant,
              height: 1.43,
            ),
            children: [
              const TextSpan(text: 'Butuh bantuan? '),
              TextSpan(
                text: 'Hubungi Administrator',
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.w600,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'Kebijakan Privasi',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: AppColors.border,
                letterSpacing: 0.5,
              ),
            ),
            Container(
              width: 4,
              height: 4,
              margin: const EdgeInsets.symmetric(horizontal: 8),
              decoration: const BoxDecoration(
                color: AppColors.outlineVariant,
                shape: BoxShape.circle,
              ),
            ),
            Text(
              'Ketentuan Layanan',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: AppColors.border,
                letterSpacing: 0.5,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Text(
          'Versi 1.0.0',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: AppColors.border,
            letterSpacing: 0.5,
          ).copyWith(color: AppColors.border.withAlpha(153)),
        ),
      ],
    );
  }
}

class _GeometricPattern extends StatelessWidget {
  const _GeometricPattern();

  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: CustomPaint(
        painter: _GeometricPainter(),
      ),
    );
  }
}

class _GeometricPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = AppColors.primary.withAlpha(8)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;

    final spacing = 48.0;
    final offset = spacing / 2;

    for (var x = -offset; x < size.width + spacing; x += spacing) {
      for (var y = -offset; y < size.height + spacing; y += spacing) {
        canvas.drawCircle(Offset(x, y), 4, paint);
        canvas.drawRect(
          Rect.fromCenter(center: Offset(x, y), width: 12, height: 12),
          paint,
        );
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
