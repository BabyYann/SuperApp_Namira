<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, PlusIcon, PencilSquareIcon, TrashIcon, 
    ExclamationTriangleIcon, InboxIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    accounts: Object, // Paginator
    filters: Object,
});

// State
const searchQuery = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);
const isLoading = ref(false);

const form = useForm({
    id: null,
    bank_name: '',
    account_number: '',
    account_name: '',
    is_active: true,
});

const deleteForm = useForm({});

// Debounce Utility
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

const performSearch = debounce((query) => {
    isLoading.value = true;
    router.get(route('yayasan.finance.accounts.index'), // Assuming I'll namespace prefix yayasan.finance
        { search: query }, 
        { 
            preserveState: true, 
            replace: true, 
            onFinish: () => isLoading.value = false 
        }
    );
}, 300);

watch(searchQuery, (q) => {
    performSearch(q);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (account) => {
    isEditing.value = true;
    form.id = account.id;
    form.bank_name = account.bank_name;
    form.account_number = account.account_number;
    form.account_name = account.account_name;
    form.is_active = Boolean(account.is_active);
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('yayasan.finance.accounts.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('yayasan.finance.accounts.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (account) => {
    itemToDelete.value = account;
    showDeleteConfirm.value = true;
};

const closeDeleteModal = () => {
    showDeleteConfirm.value = false;
    itemToDelete.value = null;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route('yayasan.finance.accounts.destroy', itemToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="Rekening Pembayaran" />

    <AuthenticatedLayout>
        <template #header>
                <!-- Top Row: Title & Description -->
                <div>
                    <h2 class="font-bold text-2xl bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-400 leading-tight">
                        Rekening Pembayaran
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Total Data: <span class="font-bold text-namira-teal">{{ accounts.total }} Rekening</span>
                    </p>
                </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4">
                <!-- Search Bar -->
                <div class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 group-focus-within:text-namira-teal transition-colors">
                            <MagnifyingGlassIcon v-if="!isLoading" class="w-5 h-5" />
                            <ArrowPathIcon v-else class="animate-spin h-5 w-5 text-namira-teal" />
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Cari Bank / No. Rek / Atas Nama..." 
                        class="pl-10 pr-4 py-2.5 w-full bg-white border border-gray-200 rounded-2xl text-sm focus:border-namira-teal focus:ring focus:ring-namira-teal/20 transition-all shadow-sm hover:shadow-md h-[46px]"
                    >
                </div>

                <!-- Add Button -->
                <button 
                    @click="openCreateModal"
                    class="px-6 py-2.5 bg-namira-teal text-white rounded-2xl font-bold shadow-lg shadow-namira-teal/30 hover:bg-teal-600 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 whitespace-nowrap active:scale-95 h-[46px]"
                >
                    <PlusIcon class="w-5 h-5" />
                    <span>Tambah Rekening</span>
                </button>
            </div>

            <!-- Main Content Container -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-sm border border-white/50 overflow-hidden">
                <!-- Account Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-white/50 border-b border-white/50">
                            <tr>
                                <th class="px-6 py-4 font-bold tracking-wider">Bank & No. Rek</th>
                                <th class="px-6 py-4 font-bold tracking-wider">Atas Nama</th>
                                <th class="px-6 py-4 font-bold tracking-wider">Status</th>
                                <th class="px-6 py-4 font-bold tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="accounts.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <InboxIcon class="w-12 h-12 mb-3 opacity-50" />
                                        <p>Belum ada data rekening bank.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="account in accounts.data" :key="account.id" class="group hover:bg-teal-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs border border-white shadow-sm">
                                            BANK
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 group-hover:text-namira-teal transition-colors">
                                                {{ account.bank_name }}
                                            </div>
                                            <div class="text-xs text-gray-500 font-mono mt-0.5">
                                                {{ account.account_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-700">{{ account.account_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border shadow-sm"
                                          :class="account.is_active ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-600 border-red-100'">
                                        {{ account.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openEditModal(account)" class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-all" title="Edit">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="confirmDelete(account)" class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Hapus">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="accounts.links.length > 3" class="p-4 border-t border-white/50 flex justify-center bg-white/30">
                     <div class="flex gap-1 bg-white/50 backdrop-blur-md p-1 rounded-xl border border-white/50 shadow-sm">
                        <template v-for="(link, k) in accounts.links" :key="k">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="link.active ? 'bg-namira-teal text-white shadow-md' : 'text-gray-500 hover:bg-white hover:text-namira-teal'"
                            />
                             <span v-else v-html="link.label" class="px-3 py-1.5 text-gray-300 text-xs font-bold"></span>
                        </template>
                     </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 border border-gray-100 transform transition-all scale-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-100">
                            {{ isEditing ? 'Edit Rekening' : 'Tambah Rekening Baru' }}
                        </h3>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="bank_name" value="Nama Bank *" />
                                <TextInput id="bank_name" v-model="form.bank_name" class="w-full mt-1 font-bold" required placeholder="Contoh: Bank Jatim" />
                                <InputError :message="form.errors.bank_name" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="account_number" value="Nomor Rekening *" />
                                <TextInput id="account_number" v-model="form.account_number" class="w-full mt-1 font-mono" required placeholder="Contoh: 1234567890" />
                                <InputError :message="form.errors.account_number" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="account_name" value="Atas Nama *" />
                                <TextInput id="account_name" v-model="form.account_name" class="w-full mt-1" required placeholder="Contoh: Yayasan Namira" />
                                <InputError :message="form.errors.account_name" class="mt-1" />
                            </div>
                            
                            <div class="flex items-center gap-2 mt-4">
                                <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded border-gray-300 text-namira-teal focus:ring-namira-teal">
                                <label for="is_active" class="text-sm font-medium text-gray-700">Status Aktif</label>
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t border-gray-50 mt-6">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition-colors">Batal</button>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-6 py-2.5 shadow-lg shadow-namira-teal/30">{{ isEditing ? 'Simpan' : 'Tambah' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
        
        <!-- Delete Modal -->
         <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeDeleteModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-gray-100 transform transition-all scale-100">
                         <div class="mx-auto flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-red-50 mb-4">
                            <ExclamationTriangleIcon class="h-8 w-8 text-red-500" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Rekening?</h3>
                        <p class="text-sm text-gray-500 mb-6">Rekening <span class="font-bold text-gray-800">"{{ itemToDelete?.bank_name }} - {{ itemToDelete?.account_number }}"</span> akan dihapus permanen.</p>
                        <div class="flex justify-center gap-3">
                            <button @click="closeDeleteModal" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                            <button @click="deleteItem" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all">Hapus Permanen</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
