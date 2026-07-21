import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/employee/providers/employee_provider.dart';

class EmployeeCheckoutScreen extends ConsumerStatefulWidget {
  const EmployeeCheckoutScreen({super.key});

  @override
  ConsumerState<EmployeeCheckoutScreen> createState() => _EmployeeCheckoutScreenState();
}

class _EmployeeCheckoutScreenState extends ConsumerState<EmployeeCheckoutScreen> {
  final _picker = ImagePicker();
  File? _photoFile;
  bool _submitting = false;

  Future<void> _pickPhoto() async {
    final file = await _picker.pickImage(source: ImageSource.camera, imageQuality: 70);
    if (file != null) {
      setState(() => _photoFile = File(file.path));
    }
  }

  String? _encodePhoto() {
    if (_photoFile == null) return null;
    final bytes = _photoFile!.readAsBytesSync();
    return base64Encode(bytes);
  }

  Future<void> _handleCheckOut() async {
    setState(() => _submitting = true);

    final error = await ref.read(employeeProvider.notifier).checkOut(
          latitude: 0,
          longitude: 0,
          photo: _encodePhoto(),
        );

    if (!mounted) return;
    setState(() => _submitting = false);

    if (error == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Check-out berhasil!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
      context.pop();
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
          'Check-Out',
          style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
        ),
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
              ),
              child: Column(
                children: [
                  Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      color: AppColors.warning.withAlpha(26),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Icon(Icons.logout_outlined, size: 32, color: AppColors.warning),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Siap Pulang?',
                    style: GoogleFonts.plusJakartaSans(fontSize: 20, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Jangan lupa foto selfie sebelum check-out',
                    textAlign: TextAlign.center,
                    style: GoogleFonts.plusJakartaSans(fontSize: 13, color: AppColors.textOnSurfaceVariant),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Text('Foto Selfie Check-Out', style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textOnSurfaceVariant, letterSpacing: 0.1)),
            const SizedBox(height: 8),
            GestureDetector(
              onTap: _pickPhoto,
              child: Container(
                width: double.infinity,
                height: 240,
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.outlineVariant, width: 1.5, strokeAlign: BorderSide.strokeAlignInside),
                ),
                child: _photoFile != null
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.file(_photoFile!, fit: BoxFit.cover),
                      )
                    : Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.camera_alt_outlined, size: 48, color: AppColors.textOnSurfaceVariant.withAlpha(128)),
                          const SizedBox(height: 12),
                          Text('Tap untuk foto selfie', style: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.textOnSurfaceVariant)),
                          const SizedBox(height: 4),
                          Text('Gunakan kamera untuk verifikasi', style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.border)),
                        ],
                      ),
              ),
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _submitting ? null : _handleCheckOut,
                style: ElevatedButton.styleFrom(textStyle: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600, letterSpacing: 0.1)),
                child: _submitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Konfirmasi Check-Out'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
