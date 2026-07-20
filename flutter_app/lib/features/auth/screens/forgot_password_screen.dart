import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _submitting = true);
    final error = await ref.read(authProvider.notifier).forgotPassword(
          _emailController.text.trim(),
        );

    if (!mounted) return;
    setState(() => _submitting = false);

    if (error == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Link reset password telah dikirim ke email Anda.'),
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
          'Lupa Password',
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
                      color: AppColors.primaryContainer.withAlpha(51),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Icon(
                      Icons.lock_reset_outlined,
                      size: 40,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Text(
                    'Lupa Password?',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 24,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                      letterSpacing: -0.02,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Masukkan email terdaftar Anda.\nKami akan mengirimkan link reset password.',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 14,
                      fontWeight: FontWeight.w400,
                      color: AppColors.textOnSurfaceVariant,
                      height: 1.5,
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
                      border: Border.all(
                        color: AppColors.outlineVariant.withAlpha(77),
                      ),
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
                        Text(
                          'Email',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textOnSurfaceVariant,
                            letterSpacing: 0.1,
                          ),
                        ),
                        const SizedBox(height: 4),
                        SizedBox(
                          height: 56,
                          child: TextFormField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            textInputAction: TextInputAction.done,
                            onFieldSubmitted: (_) => _handleSubmit(),
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 16,
                              fontWeight: FontWeight.w400,
                              color: AppColors.textPrimary,
                            ),
                            decoration: InputDecoration(
                              hintText: 'nama@email.com',
                              hintStyle: GoogleFonts.plusJakartaSans(
                                fontSize: 16,
                                fontWeight: FontWeight.w400,
                                color: AppColors.border,
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
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) return 'Email harus diisi';
                              if (!value.contains('@')) return 'Email tidak valid';
                              return null;
                            },
                          ),
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
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Kirim Link Reset'),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  TextButton(
                    onPressed: () => context.pop(),
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
}
