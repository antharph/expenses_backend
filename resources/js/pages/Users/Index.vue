<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Coins, ReceiptText, ArrowRight } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import usersRoute from '@/routes/users';
import type { User } from '@/types';

// Declare props
const props = defineProps<{
    users: (User & {
        budgets_count: number;
        expenses_count: number;
    })[];
}>();

const { getInitials } = useInitials();

// Set layout breadcrumbs
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: usersRoute.index(),
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
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Users"
            description="Manage registered users and monitor their budgets and expenses count."
        />

        <div
            v-if="props.users.length === 0"
            class="flex flex-1 items-center justify-center rounded-lg border border-dashed p-8 text-center"
        >
            <div
                class="mx-auto flex max-w-[420px] flex-col items-center justify-center text-center"
            >
                <div
                    class="flex h-20 w-20 items-center justify-center rounded-full bg-muted"
                >
                    <UserIcon class="h-10 w-10 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No users found</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    There are no users registered in the system yet.
                </p>
            </div>
        </div>

        <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Card
                v-for="user in props.users"
                :key="user.id"
                class="group relative overflow-hidden transition-all duration-300 hover:border-muted-foreground/30 hover:shadow-md dark:hover:border-muted-foreground/20"
            >
                <CardContent class="p-6">
                    <div class="flex items-start gap-4">
                        <Avatar
                            class="h-12 w-12 rounded-lg border border-sidebar-border bg-muted"
                        >
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded-lg text-sm font-semibold"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex-1 space-y-1">
                            <h3
                                class="leading-none font-semibold tracking-tight transition-colors group-hover:text-primary"
                            >
                                {{ user.name }}
                            </h3>
                            <p
                                class="line-clamp-1 text-xs text-muted-foreground"
                            >
                                {{ user.email }}
                            </p>
                            <p class="text-[10px] text-muted-foreground/80">
                                Joined {{ formatDate(user.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-6 grid grid-cols-2 gap-4 border-t border-muted/40 pt-4"
                    >
                        <div class="flex flex-col gap-1">
                            <span
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Coins class="h-3 w-3 text-amber-500" /> Budgets
                            </span>
                            <span class="text-lg font-semibold tracking-tight">
                                {{ user.budgets_count }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <ReceiptText class="h-3 w-3 text-emerald-500" />
                                Expenses
                            </span>
                            <span class="text-lg font-semibold tracking-tight">
                                {{ user.expenses_count }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Link
                            :href="usersRoute.show(user.id)"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary transition-all hover:translate-x-1"
                        >
                            View Profile
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
