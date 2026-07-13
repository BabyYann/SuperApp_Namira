<script setup>
import PromotionStatusBadge from './PromotionStatusBadge.vue';
import PromotionReasonBadge from './PromotionReasonBadge.vue';

defineProps({
    students: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <div class="bg-white border rounded-2xl shadow-sm overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16">No</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-36">NIS</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-36">Kelas Asal</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Alasan Pelanggaran</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <tr v-for="(student, index) in students" :key="student.student_id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700">{{ student.nis }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ student.nama }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.classroom }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <PromotionStatusBadge :status="student.status" />
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2" v-if="student.violations.length > 0">
                                <PromotionReasonBadge 
                                    v-for="v in student.violations" 
                                    :key="v.code" 
                                    :code="v.code" 
                                    :message="v.message" 
                                />
                            </div>
                            <span v-else class="text-xs text-gray-400 italic">Tidak ada catatan sanksi/SPP</span>
                        </td>
                    </tr>
                    <tr v-if="students.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 italic">
                            Tidak ada data siswa ditemukan
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
