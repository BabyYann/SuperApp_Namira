import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';

class ResetPasswordScreen extends ConsumerStatefulWidget {
  final String? token;
  final String? email;

  const ResetPasswordScreen({super.key, this.token, this.email});

  @override
  ConsumerState<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends ConsumerState<ResetPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _emailController;
  late final TextEditingController _tokenController;
  late final TextEditingController _passwordController;
  late final TextEditingController _confirmController;
  bool _submitting = false;
  bool _obscurePassword = true;
  bool _obscureConfirm = true;

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController(text: widget.email ?? '');
    _tokenController = TextEditingController(text: widget.token ?? '');
    _passwordController = TextEditingController();
    _confirmController = TextEditingController();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _tokenController.dispose();
    _passwordController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _submitting = true);
    final error = await ref.read(authProvider.notifier).resetPassword(
          email: _emailController.text.trim(),
          token: _tokenController.text.trim(),
          password: _passwordController.text,
          passwordConfirmation: _confirmController.text,
        );

    if (!mounted) return;
    setState(() => _submitting = false);

    if (error == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Password berhasil direset. Silakan login.'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
      context.go('/login');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(
          'Reset Password',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withAlpha(26),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Icon(
                      Icons.lock_open_outlined,
                      size: 40,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Text(
                    'Reset Password',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 24,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                      letterSpacing: -0.02,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Masukkan password baru Anda.',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 14,
                      fontWeight: FontWeight.w400,
                      color: AppColors.textOnSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 32),
                  Container(
                    width: double.infinity,
                    constraints: const BoxConstraints(maxWidth: 480),
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withAlpha(13),
                          blurRadius: 3,
                          offset: const Offset(0, 1),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Email', style: _labelStyle()),
                        const SizedBox(height: 4),
                        _buildTextField(
                          controller: _emailController,
                          hint: 'nama@email.com',
                          icon: Icons.email_outlined,
                          readOnly: widget.email != null,
                          validator: (v) {
                            if (v == null || v.isEmpty) return 'Email harus diisi';
                            if (!v.contains('@')) return 'Email tidak valid';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        Text('Token Reset', style: _labelStyle()),
                        const SizedBox(height: 4),
                        _buildTextField(
                          controller: _tokenController,
                          hint: 'Masukkan token dari email',
                          icon: Icons.vpn_key_outlined,
                          readOnly: widget.token != null,
                          validator: (v) {
                            if (v == null || v.isEmpty) return 'Token harus diisi';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        Text('Password Baru', style: _labelStyle()),
                        const SizedBox(height: 4),
                        _buildPasswordField(
                          controller: _passwordController,
                          obscure: _obscurePassword,
                          onToggle: () => setState(() => _obscurePassword = !_obscurePassword),
                          validator: (v) {
                            if (v == null || v.isEmpty) return 'Password harus diisi';
                            if (v.length < 8) return 'Minimal 8 karakter';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        Text('Konfirmasi Password', style: _labelStyle()),
                        const SizedBox(height: 4),
                        _buildPasswordField(
                          controller: _confirmController,
                          obscure: _obscureConfirm,
                          onToggle: () => setState(() => _obscureConfirm = !_obscureConfirm),
                          validator: (v) {
                            if (v == null || v.isEmpty) return 'Konfirmasi password harus diisi';
                            if (v != _passwordController.text) return 'Password tidak cocok';
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),
                        SizedBox(
                          width: double.infinity,
                          height: 48,
                          child: ElevatedButton(
                            onPressed: _submitting ? null : _handleSubmit,
                            style: ElevatedButton.styleFrom(
                              textStyle: GoogleFonts.plusJakartaSans(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                letterSpacing: 0.1,
                              ),
                            ),
                            child: _submitting
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                  )
                                : const Text('Reset Password'),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  TextButton(
                    onPressed: () => context.go('/login'),
                    child: Text(
                      'Kembali ke Login',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  TextStyle _labelStyle() => GoogleFonts.plusJakartaSans(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: AppColors.textOnSurfaceVariant,
        letterSpacing: 0.1,
      );

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    bool readOnly = false,
    String? Function(String?)? validator,
  }) {
    return SizedBox(
      height: 56,
      child: TextFormField(
        controller: controller,
        readOnly: readOnly,
        style: GoogleFonts.plusJakartaSans(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w400,
            color: AppColors.border,
          ),
          prefixIcon: Icon(icon, size: 20),
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
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
        ),
        validator: validator,
      ),
    );
  }

  Widget _buildPasswordField({
    required TextEditingController controller,
    required bool obscure,
    required VoidCallback onToggle,
    required String? Function(String?)? validator,
  }) {
    return SizedBox(
      height: 56,
      child: TextFormField(
        controller: controller,
        obscureText: obscure,
        style: GoogleFonts.plusJakartaSans(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          hintText: 'Minimal 8 karakter',
          hintStyle: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w400,
            color: AppColors.border,
          ),
          prefixIcon: const Icon(Icons.lock_outline, size: 20),
          prefixIconColor: AppColors.textOnSurfaceVariant,
          suffixIcon: IconButton(
            icon: Icon(obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 20),
            onPressed: onToggle,
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
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
        ),
        validator: validator,
      ),
    );
  }
}
