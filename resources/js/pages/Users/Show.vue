<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Coins,
    ReceiptText,
    Calendar,
    Globe,
    Mail,
    ChevronLeft,
    Check,
    X,
    Store as StoreIcon,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import users from '@/routes/users';
import type { User, Budget, Expense } from '@/types';

// Extend the User type to include our count and list attributes
interface UserDetail extends User {
    budgets_count: number;
    expenses_count: number;
    budgets: Budget[];
    expenses: Expense[];
}

defineProps<{
    user: UserDetail;
}>();

const { getInitials } = useInitials();

// Active tab tracks which list is currently displayed: 'budgets' or 'expenses'
const activeTab = ref<'budgets' | 'expenses'>('budgets');

// Set layout breadcrumbs
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: users.index(),
            },
            {
                title: 'User Profile',
                href: '#',
            },
        ],
    },
});

// Format date helper
function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Format currency helper
function formatCurrency(amount: string | number) {
    const val = typeof amount === 'string' ? parseFloat(amount) : amount;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PHP', // Default currency for expenses app
    }).format(val);
}

// Capitalize first letter helper
function capitalize(str: string) {
    if (!str) {
        return '';
    }

    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>

<template>
    <Head :title="`${user.name} - Profile`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <!-- Back navigation link -->
        <div>
            <Link
                :href="users.index()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ChevronLeft class="h-4 w-4" /> Back to users list
            </Link>
        </div>

        <!-- User Identity Panel -->
        <Card class="overflow-hidden border border-sidebar-border bg-card">
            <CardContent class="p-6 md:p-8">
                <div
                    class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center"
                    >
                        <Avatar
                            class="h-20 w-20 rounded-xl border-2 border-primary/20 bg-muted shadow-sm"
                        >
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded-xl text-xl font-bold"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="space-y-1.5">
                            <h2 class="text-2xl font-bold tracking-tight">
                                {{ user.name }}
                            </h2>
                            <div
                                class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground"
                            >
                                <span class="flex items-center gap-1.5">
                                    <Mail
                                        class="h-3.5 w-3.5 text-muted-foreground/75"
                                    />
                                    {{ user.email }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Globe
                                        class="h-3.5 w-3.5 text-muted-foreground/75"
                                    />
                                    {{
                                        user.timezone || 'Asia/Manila (Default)'
                                    }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Calendar
                                        class="h-3.5 w-3.5 text-muted-foreground/75"
                                    />
                                    Registered {{ formatDate(user.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Dynamic Tabs / Stats Section -->
        <div class="grid gap-6 sm:grid-cols-2">
            <!-- Budgets Stat Card -->
            <button
                @click="activeTab = 'budgets'"
                class="w-full text-left focus:outline-none"
                aria-label="View user budgets"
            >
                <Card
                    class="cursor-pointer border-2 transition-all duration-300"
                    :class="[
                        activeTab === 'budgets'
                            ? 'border-amber-500/70 bg-amber-500/5 shadow-sm dark:border-amber-500/55 dark:bg-amber-500/10'
                            : 'border-transparent hover:border-muted-foreground/20 hover:shadow-xs',
                    ]"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <div class="space-y-0.5">
                            <CardTitle
                                class="text-sm font-medium text-muted-foreground"
                                >Budgets Count</CardTitle
                            >
                            <CardDescription class="text-xs"
                                >Active budget envelopes</CardDescription
                            >
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg transition-colors"
                            :class="
                                activeTab === 'budgets'
                                    ? 'bg-amber-500 text-white'
                                    : 'bg-muted text-amber-500'
                            "
                        >
                            <Coins class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ user.budgets_count }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Click here to view the list of budgets
                        </p>
                    </CardContent>
                </Card>
            </button>

            <!-- Expenses Stat Card -->
            <button
                @click="activeTab = 'expenses'"
                class="w-full text-left focus:outline-none"
                aria-label="View user expenses"
            >
                <Card
                    class="cursor-pointer border-2 transition-all duration-300"
                    :class="[
                        activeTab === 'expenses'
                            ? 'border-emerald-500/70 bg-emerald-500/5 shadow-sm dark:border-emerald-500/55 dark:bg-emerald-500/10'
                            : 'border-transparent hover:border-muted-foreground/20 hover:shadow-xs',
                    ]"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <div class="space-y-0.5">
                            <CardTitle
                                class="text-sm font-medium text-muted-foreground"
                                >Expenses Count</CardTitle
                            >
                            <CardDescription class="text-xs"
                                >Logged financial transactions</CardDescription
                            >
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg transition-colors"
                            :class="
                                activeTab === 'expenses'
                                    ? 'bg-emerald-500 text-white'
                                    : 'bg-muted text-emerald-500'
                            "
                        >
                            <ReceiptText class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ user.expenses_count }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Click here to view the list of expenses
                        </p>
                    </CardContent>
                </Card>
            </button>
        </div>

        <!-- Detail Lists Table Section -->
        <Card class="border border-sidebar-border bg-card">
            <CardHeader
                class="flex flex-col gap-1 border-b border-muted/30 pb-4"
            >
                <CardTitle class="flex items-center gap-2 text-lg font-bold">
                    <component
                        :is="activeTab === 'budgets' ? Coins : ReceiptText"
                        class="h-5 w-5"
                        :class="
                            activeTab === 'budgets'
                                ? 'text-amber-500'
                                : 'text-emerald-500'
                        "
                    />
                    {{
                        activeTab === 'budgets'
                            ? 'Budgets List'
                            : 'Expenses List'
                    }}
                </CardTitle>
                <CardDescription>
                    {{
                        activeTab === 'budgets'
                            ? "Detailed list of the user's active budgets and categories."
                            : "Chronological ledger of user's uploaded and logged expenses."
                    }}
                </CardDescription>
            </CardHeader>

            <CardContent class="p-0">
                <!-- Budgets List View -->
                <div v-if="activeTab === 'budgets'">
                    <div
                        v-if="user.budgets.length === 0"
                        class="flex flex-col items-center justify-center px-4 py-16 text-center"
                    >
                        <Coins
                            class="mb-3 h-12 w-12 text-muted-foreground/50"
                        />
                        <h4 class="font-semibold text-muted-foreground">
                            No Budgets
                        </h4>
                        <p
                            class="mt-1 max-w-[280px] text-xs text-muted-foreground"
                        >
                            This user hasn't set up any budgets yet.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="border-b border-muted/30 bg-muted/40 text-xs font-semibold text-muted-foreground uppercase"
                            >
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4 text-right">
                                        Limit Amount
                                    </th>
                                    <th class="px-6 py-4">Reset Interval</th>
                                    <th class="px-6 py-4 text-center">
                                        Rollover
                                    </th>
                                    <th class="px-6 py-4">Linked Categories</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-muted/20">
                                <tr
                                    v-for="budget in user.budgets"
                                    :key="budget.id"
                                    class="transition-colors hover:bg-muted/10"
                                >
                                    <td
                                        class="px-6 py-4 font-semibold text-foreground"
                                    >
                                        {{ budget.name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right font-medium text-amber-600 dark:text-amber-400"
                                    >
                                        {{ formatCurrency(budget.amount) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge
                                            variant="secondary"
                                            class="font-normal capitalize"
                                        >
                                            {{ capitalize(budget.reset_type) }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center">
                                            <Badge
                                                :variant="
                                                    budget.rollover
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                class="gap-1 font-normal"
                                                :class="
                                                    budget.rollover
                                                        ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                                                        : 'text-muted-foreground'
                                                "
                                            >
                                                <component
                                                    :is="
                                                        budget.rollover
                                                            ? Check
                                                            : X
                                                    "
                                                    class="h-3 w-3"
                                                />
                                                {{
                                                    budget.rollover
                                                        ? 'Yes'
                                                        : 'No'
                                                }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="flex max-w-[300px] flex-wrap gap-1.5"
                                        >
                                            <span
                                                v-if="
                                                    !budget.categories ||
                                                    budget.categories.length ===
                                                        0
                                                "
                                                class="text-xs text-muted-foreground"
                                            >
                                                All Categories
                                            </span>
                                            <Badge
                                                v-for="cat in budget.categories"
                                                :key="cat.id"
                                                variant="outline"
                                                class="text-[10px] font-normal"
                                            >
                                                {{ cat.name }}
                                            </Badge>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Expenses List View -->
                <div v-else>
                    <div
                        v-if="user.expenses.length === 0"
                        class="flex flex-col items-center justify-center px-4 py-16 text-center"
                    >
                        <ReceiptText
                            class="mb-3 h-12 w-12 text-muted-foreground/50"
                        />
                        <h4 class="font-semibold text-muted-foreground">
                            No Expenses
                        </h4>
                        <p
                            class="mt-1 max-w-[280px] text-xs text-muted-foreground"
                        >
                            This user hasn't logged any expenses yet.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="border-b border-muted/30 bg-muted/40 text-xs font-semibold text-muted-foreground uppercase"
                            >
                                <tr>
                                    <th class="px-6 py-4">Item</th>
                                    <th class="px-6 py-4">Category</th>
                                    <th class="px-6 py-4">Store</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">
                                        Unit Price
                                    </th>
                                    <th class="px-6 py-4 text-right">
                                        Total Price
                                    </th>
                                    <th class="px-6 py-4">Transaction Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-muted/20">
                                <tr
                                    v-for="expense in user.expenses"
                                    :key="expense.id"
                                    class="transition-colors hover:bg-muted/10"
                                >
                                    <td class="px-6 py-4">
                                        <div
                                            class="line-clamp-2 max-w-[220px] leading-snug font-semibold text-foreground"
                                        >
                                            {{ expense.item }}
                                        </div>
                                        <div
                                            v-if="
                                                expense.transaction_number ||
                                                expense.invoice_number
                                            "
                                            class="mt-0.5 flex gap-2 text-[10px] text-muted-foreground"
                                        >
                                            <span
                                                v-if="
                                                    expense.transaction_number
                                                "
                                                >TX:
                                                {{
                                                    expense.transaction_number
                                                }}</span
                                            >
                                            <span v-if="expense.invoice_number"
                                                >INV:
                                                {{
                                                    expense.invoice_number
                                                }}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge
                                            v-if="expense.category"
                                            variant="secondary"
                                            class="border-none bg-emerald-500/10 font-medium text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ expense.category.name }}
                                        </Badge>
                                        <span
                                            v-else
                                            class="text-xs text-muted-foreground"
                                            >Uncategorized</span
                                        >
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="expense.store"
                                            class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                        >
                                            <StoreIcon class="h-3 w-3" />
                                            {{ expense.store.name }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs text-muted-foreground"
                                            >-</span
                                        >
                                    </td>
                                    <td
                                        class="px-6 py-4 text-center text-muted-foreground"
                                    >
                                        {{ expense.quantity }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right font-medium text-muted-foreground"
                                    >
                                        {{ formatCurrency(expense.price) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(expense.total) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-xs text-muted-foreground"
                                    >
                                        {{ formatDate(expense.transaction_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
