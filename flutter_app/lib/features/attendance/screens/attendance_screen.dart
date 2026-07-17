import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/attendance/providers/attendance_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({super.key});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  String _selectedType = 'present';
  final _noteController = TextEditingController();
  File? _photoFile;
  Position? _currentPosition;
  bool _gettingLocation = false;
  String? _locationError;

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(attendanceProvider.notifier).loadToday());
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _getCurrentLocation() async {
    setState(() => _gettingLocation = true);
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied ||
            permission == LocationPermission.deniedForever) {
          setState(() {
            _locationError = 'Izin lokasi ditolak';
            _gettingLocation = false;
          });
          return;
        }
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 10),
        ),
      );
      setState(() {
        _currentPosition = position;
        _gettingLocation = false;
        _locationError = null;
      });
    } catch (e) {
      setState(() {
        _locationError = 'Gagal mendapatkan lokasi: $e';
        _gettingLocation = false;
      });
    }
  }

  Future<void> _takePhoto() async {
    final picker = ImagePicker();
    final image = await picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 80,
      maxWidth: 800,
    );
    if (image != null) {
      setState(() => _photoFile = File(image.path));
    }
  }

  String? _photoToBase64() {
    if (_photoFile == null) return null;
    final bytes = _photoFile!.readAsBytesSync();
    return base64Encode(bytes);
  }

  Future<void> _handleCheckIn() async {
    if (_currentPosition == null) {
      await _getCurrentLocation();
      if (_currentPosition == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_locationError ?? 'Aktifkan GPS terlebih dahulu'),
              backgroundColor: AppColors.error,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
          );
        }
        return;
      }
    }

    final error = await ref.read(attendanceProvider.notifier).checkIn(
      type: _selectedType,
      latitude: _currentPosition!.latitude,
      longitude: _currentPosition!.longitude,
      photo: _photoToBase64(),
      note: _noteController.text.isNotEmpty ? _noteController.text : null,
    );

    if (error != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    } else if (mounted) {
      setState(() => _photoFile = null);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Berhasil check-in!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    }
  }

  Future<void> _handleCheckOut() async {
    if (_currentPosition == null) {
      await _getCurrentLocation();
      if (_currentPosition == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_locationError ?? 'Aktifkan GPS terlebih dahulu'),
              backgroundColor: AppColors.error,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
          );
        }
        return;
      }
    }

    final error = await ref.read(attendanceProvider.notifier).checkOut(
      latitude: _currentPosition!.latitude,
      longitude: _currentPosition!.longitude,
      photo: _photoToBase64(),
    );

    if (error != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    } else if (mounted) {
      setState(() => _photoFile = null);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Berhasil check-out!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final attState = ref.watch(attendanceProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Presensi'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: attState.status == AttendanceStatus.loading
          ? const LoadingWidget(message: 'Memuat data presensi...')
          : SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildTodayCard(attState),
                  const SizedBox(height: 20),
                  if (!attState.hasCheckedIn) _buildCheckInSection(attState),
                  if (attState.hasCheckedIn && !attState.hasCheckedOut)
                    _buildCheckOutSection(),
                  const SizedBox(height: 20),
                  _buildInfoSection(),
                ],
              ),
            ),
    );
  }

  Widget _buildTodayCard(AttendanceState attState) {
    final attendance = attState.todayAttendance;
    final hasCheckedIn = attState.hasCheckedIn;
    final hasCheckedOut = attState.hasCheckedOut;

    Color statusColor;
    String statusText;

    if (hasCheckedOut) {
      statusColor = AppColors.info;
      statusText = 'Selesai';
    } else if (hasCheckedIn) {
      statusColor = AppColors.success;
      statusText = 'Sudah Check-In';
    } else {
      statusColor = AppColors.warning;
      statusText = 'Belum Absen';
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [statusColor, statusColor.withAlpha(179)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: statusColor.withAlpha(77),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          NamiraBadge(
            label: statusText,
            color: Colors.white.withAlpha(51),
            textColor: Colors.white,
          ),
          const SizedBox(height: 16),
          if (hasCheckedIn && attendance != null) ...[
            Row(
              children: [
                const Icon(Icons.login, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Text(
                  'Check-In: ${attendance['check_in_time'] ?? '-'}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            if (attendance['status'] == 'late' &&
                attendance['late_minutes'] != null) ...[
              const SizedBox(height: 4),
              Text(
                'Terlambat ${attendance['late_minutes']} menit',
                style: TextStyle(color: Colors.white.withAlpha(204), fontSize: 12),
              ),
            ],
            if (hasCheckedOut) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.logout, color: Colors.white, size: 20),
                  const SizedBox(width: 8),
                  Text(
                    'Check-Out: ${attendance['check_out_time'] ?? '-'}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ],
          ] else
            const Text(
              'Anda belum melakukan presensi hari ini',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w500,
                fontSize: 15,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildCheckInSection(AttendanceState attState) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Jenis Kehadiran',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _TypeChip(
              label: 'WFO (Hadir)',
              icon: Icons.work_outlined,
              isSelected: _selectedType == 'present',
              onTap: () => setState(() => _selectedType = 'present'),
            ),
            _TypeChip(
              label: 'Dinas Luar',
              icon: Icons.directions_car_outlined,
              isSelected: _selectedType == 'business_trip',
              onTap: () => setState(() => _selectedType = 'business_trip'),
            ),
            _TypeChip(
              label: 'Sakit',
              icon: Icons.sick_outlined,
              isSelected: _selectedType == 'sick',
              onTap: () => setState(() => _selectedType = 'sick'),
            ),
            _TypeChip(
              label: 'Izin',
              icon: Icons.event_available_outlined,
              isSelected: _selectedType == 'permit',
              onTap: () => setState(() => _selectedType = 'permit'),
            ),
          ],
        ),
        if (_selectedType != 'present') ...[
          const SizedBox(height: 16),
          TextField(
            controller: _noteController,
            maxLines: 3,
            decoration: InputDecoration(
              labelText: 'Keterangan',
              hintText: 'Masukkan keterangan...',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
        ],
        const SizedBox(height: 16),
        _buildLocationStatus(),
        const SizedBox(height: 12),
        _buildPhotoPreview(),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: attState.status == AttendanceStatus.loading
                ? null
                : _handleCheckIn,
            icon: attState.status == AttendanceStatus.loading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.fingerprint, size: 24),
            label: const Text('Check-In Sekarang'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCheckOutSection() {
    final attState = ref.watch(attendanceProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildLocationStatus(),
        const SizedBox(height: 12),
        _buildPhotoPreview(),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: attState.status == AttendanceStatus.loading
                ? null
                : _handleCheckOut,
            icon: attState.status == AttendanceStatus.loading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.logout, size: 24),
            label: const Text('Check-Out Sekarang'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.secondary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildLocationStatus() {
    return GestureDetector(
      onTap: _gettingLocation ? null : _getCurrentLocation,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: _currentPosition != null
                ? AppColors.success
                : _locationError != null
                    ? AppColors.error
                    : AppColors.border,
          ),
        ),
        child: Row(
          children: [
            Icon(
              Icons.location_on_outlined,
              size: 18,
              color: _currentPosition != null
                  ? AppColors.success
                  : _locationError != null
                      ? AppColors.error
                      : AppColors.textSecondary,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _gettingLocation
                  ? const Text('Mendapatkan lokasi...', style: TextStyle(fontSize: 12, color: AppColors.textSecondary))
                  : _currentPosition != null
                      ? Text(
                          'Lat: ${_currentPosition!.latitude.toStringAsFixed(5)}, Lng: ${_currentPosition!.longitude.toStringAsFixed(5)}',
                          style: const TextStyle(fontSize: 12, color: AppColors.success),
                        )
                      : Text(
                          _locationError ?? 'Tap untuk ambil lokasi',
                          style: TextStyle(
                            fontSize: 12,
                            color: _locationError != null ? AppColors.error : AppColors.textSecondary,
                          ),
                        ),
            ),
            if (_gettingLocation)
              const SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildPhotoPreview() {
    return Row(
      children: [
        Expanded(
          child: _photoFile != null
              ? ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Stack(
                    alignment: Alignment.topRight,
                    children: [
                      Image.file(_photoFile!, height: 120, width: double.infinity, fit: BoxFit.cover),
                      GestureDetector(
                        onTap: () => setState(() => _photoFile = null),
                        child: Container(
                          margin: const EdgeInsets.all(4),
                          padding: const EdgeInsets.all(2),
                          decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle),
                          child: const Icon(Icons.close, color: Colors.white, size: 16),
                        ),
                      ),
                    ],
                  ),
                )
              : GestureDetector(
                  onTap: _takePhoto,
                  child: Container(
                    height: 80,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.border, style: BorderStyle.solid),
                    ),
                    child: const Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.camera_alt_outlined, size: 24, color: AppColors.textSecondary),
                        SizedBox(height: 4),
                        Text('Ambil Foto (Opsional)', style: TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                      ],
                    ),
                  ),
                ),
        ),
      ],
    );
  }

  Widget _buildInfoSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Informasi',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 8),
        _InfoTile(
          icon: Icons.info_outline,
          title: 'Keterangan Presensi',
          subtitle: 'Check-in harus dilakukan di dalam area sekolah (radius lokasi absensi).',
        ),
        const SizedBox(height: 8),
        _InfoTile(
          icon: Icons.access_time,
          title: 'Jam Kerja',
          subtitle: 'Keterlambatan dihitung setelah jam masuk toleransi.',
        ),
      ],
    );
  }
}

class _TypeChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  const _TypeChip({
    required this.label,
    required this.icon,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary.withAlpha(26) : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 18, color: isSelected ? AppColors.primary : AppColors.textSecondary),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isSelected ? AppColors.primary : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;

  const _InfoTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppColors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                ),
                const SizedBox(height: 2),
                Text(subtitle, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
