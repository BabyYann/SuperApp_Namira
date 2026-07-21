import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/employee/providers/employee_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/scale_press.dart';

class EmployeeCheckinScreen extends ConsumerStatefulWidget {
  const EmployeeCheckinScreen({super.key});

  @override
  ConsumerState<EmployeeCheckinScreen> createState() => _EmployeeCheckinScreenState();
}

class _EmployeeCheckinScreenState extends ConsumerState<EmployeeCheckinScreen> {
  String _selectedType = 'present';
  final _noteController = TextEditingController();
  final _picker = ImagePicker();
  File? _photoFile;
  bool _submitting = false;

  final _types = [
    {'value': 'present', 'label': 'WFO', 'icon': Icons.business_center_outlined},
    {'value': 'business_trip', 'label': 'Dinas Luar', 'icon': Icons.flight_takeoff_outlined},
    {'value': 'sick', 'label': 'Sakit', 'icon': Icons.healing_outlined},
    {'value': 'permit', 'label': 'Izin', 'icon': Icons.description_outlined},
  ];

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

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

  Future<void> _handleCheckIn() async {
    setState(() => _submitting = true);

    final error = await ref.read(employeeProvider.notifier).checkIn(
          type: _selectedType,
          photo: _encodePhoto(),
          note: _noteController.text.isNotEmpty ? _noteController.text : null,
        );

    if (!mounted) return;
    setState(() => _submitting = false);

    if (error == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Check-in berhasil!'),
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
          'Check-In',
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
            Text('Tipe Absensi', style: _labelStyle()),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _types.map((t) {
                final selected = _selectedType == t['value'];
                return ScalePress(
                  onTap: () => setState(() => _selectedType = t['value'] as String),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      color: selected ? AppColors.primary : Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: selected ? AppColors.primary : AppColors.outlineVariant,
                        width: selected ? 2 : 1,
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(t['icon'] as IconData, size: 18, color: selected ? Colors.white : AppColors.textOnSurfaceVariant),
                        const SizedBox(width: 8),
                        Text(
                          t['label'] as String,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: selected ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
            if (_selectedType == 'sick' || _selectedType == 'permit') ...[
              const SizedBox(height: 24),
              Text('Foto Surat/Dokumen', style: _labelStyle()),
              const SizedBox(height: 8),
              GestureDetector(
                onTap: _pickPhoto,
                child: Container(
                  width: double.infinity,
                  height: 120,
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
                            Icon(Icons.camera_alt_outlined, size: 32, color: AppColors.textOnSurfaceVariant.withAlpha(128)),
                            const SizedBox(height: 8),
                            Text('Tap untuk foto dokumen', style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textOnSurfaceVariant)),
                          ],
                        ),
                ),
              ),
            ],
            if (_selectedType == 'present' || _selectedType == 'business_trip') ...[
              const SizedBox(height: 24),
              Text('Foto Selfie', style: _labelStyle()),
              const SizedBox(height: 8),
              GestureDetector(
                onTap: _pickPhoto,
                child: Container(
                  width: double.infinity,
                  height: 200,
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
                            Icon(Icons.camera_alt_outlined, size: 40, color: AppColors.textOnSurfaceVariant.withAlpha(128)),
                            const SizedBox(height: 12),
                            Text('Tap untuk foto selfie', style: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.textOnSurfaceVariant)),
                            const SizedBox(height: 4),
                            Text('Gunakan kamera untuk verifikasi', style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.border)),
                          ],
                        ),
                ),
              ),
            ],
            const SizedBox(height: 24),
            Text('Catatan (opsional)', style: _labelStyle()),
            const SizedBox(height: 8),
            SizedBox(
              height: 100,
              child: TextFormField(
                controller: _noteController,
                maxLines: 3,
                style: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.textPrimary),
                decoration: InputDecoration(
                  hintText: 'Tambahkan catatan...',
                  hintStyle: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.border),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.outlineVariant)),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.outlineVariant)),
                  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.primary, width: 2)),
                ),
              ),
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _submitting ? null : _handleCheckIn,
                style: ElevatedButton.styleFrom(textStyle: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600, letterSpacing: 0.1)),
                child: _submitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Konfirmasi Check-In'),
              ),
            ),
          ],
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
}
