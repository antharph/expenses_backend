<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

const benefits = [
    {
        title: 'See your week at a glance',
        description:
            'A clean dashboard shows your weekly total, daily spending trends, and where your money went by category.',
    },
    {
        title: 'Budget by pay period',
        description:
            'Set budgets that match how you actually get paid. Track what’s spent, what’s remaining, and when you’re over budget.',
    },
    {
        title: 'Rollover what you don’t use',
        description:
            'Carry unused funds into your next period so good habits actually pay off.',
    },
    {
        title: 'Capture receipts in seconds',
        description:
            'Take a photo or upload from your library. Attach proof to every expense without extra steps.',
    },
    {
        title: 'Manual entry when you need it',
        description:
            'Add item, quantity, and price yourself for quick purchases without a receipt.',
    },
    {
        title: 'Filter and find anything',
        description:
            'Browse all expenses by date range or category. Review details, edit, or remove entries with ease.',
    },
    {
        title: 'Built for real life',
        description:
            'Sign in with Google or email. Manage your profile and account security in one place.',
    },
] as const;

const audience = [
    'Anyone who wants a straightforward personal expense tracker',
    'People who budget by paycheck, not just by calendar month',
    'Shoppers who want receipts and spending history in one place',
    'Anyone tired of juggling notes, photos, and mental math',
] as const;

const appStoreUrl =
    'https://apps.apple.com/ph/app/mai-expenses-tracker/id6771236010';

const appStoreBadgeBlack =
    'https://tools.applemediaservices.com/api/badges/download-on-the-app-store/black/en-us?size=250x83&releaseDate=0';

const appStoreBadgeWhite =
    'https://tools.applemediaservices.com/api/badges/download-on-the-app-store/white/en-us?size=250x83&releaseDate=0';
</script>

<template>
    <Head title="MaiExpenses — Track spending the easy way" />

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            v-if="$page.props.auth.user"
            class="mb-6 w-full max-w-5xl text-sm"
        >
            <nav class="flex items-center justify-end">
                <Link
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
            </nav>
        </header>

        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-5xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-16 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <p
                        class="mb-2 text-xs font-medium tracking-wide text-[#f53003] uppercase dark:text-[#FF4433]"
                    >
                        Personal expense tracking
                    </p>
                    <h1 class="mb-4 text-2xl font-medium leading-snug lg:text-3xl">
                        Understand where your money goes—and stay in control
                        between paydays
                    </h1>
                    <p class="mb-3 text-[#706f6c] dark:text-[#A1A09A]">
                        Whether you snap a receipt on the go or type in a
                        purchase manually, MaiExpenses keeps your spending
                        organized in one simple app.
                    </p>
                    <p class="mb-4 text-[#706f6c] dark:text-[#A1A09A]">
                        No spreadsheets. No guesswork. Just a clear picture of
                        what you’ve spent and what you have left.
                    </p>
                    <p class="mb-8">
                        <a
                            :href="appStoreUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]"
                        >
                            Download on the App Store
                        </a>
                    </p>

                    <h2 class="mb-4 font-medium">Why MaiExpenses?</h2>
                    <ul class="mb-8 flex flex-col gap-3">
                        <li
                            v-for="benefit in benefits"
                            :key="benefit.title"
                            class="flex gap-3"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                aria-hidden="true"
                            >
                                •
                            </span>
                            <span>
                                <span class="font-medium">{{ benefit.title }}</span>
                                <span class="text-[#706f6c] dark:text-[#A1A09A]">
                                    — {{ benefit.description }}
                                </span>
                            </span>
                        </li>
                    </ul>

                    <p class="text-[#706f6c] dark:text-[#A1A09A]">
                        Need help?
                        <a
                            href="/support"
                            class="font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]"
                        >
                            Contact support
                        </a>
                        .
                    </p>
                </div>

                <div
                    class="relative flex w-full shrink-0 flex-col justify-center overflow-hidden rounded-t-lg bg-[#fff2f2] p-8 lg:-ml-px lg:w-[400px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#1D0002]"
                >
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    />

                    <div class="relative space-y-8 text-sm">
                        <div>
                            <p
                                class="mb-2 text-4xl font-semibold tracking-tight text-[#F53003] dark:text-[#F61500]"
                            >
                                MaiExpenses
                            </p>
                            <p class="mb-5 text-[#706f6c] dark:text-[#A1A09A]">
                                Take the guesswork out of your spending.
                            </p>
                            <a
                                :href="appStoreUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Download MaiExpenses on the App Store"
                                class="inline-block transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#F53003]"
                            >
                                <img
                                    :src="appStoreBadgeBlack"
                                    alt="Download on the App Store"
                                    width="120"
                                    height="40"
                                    class="h-10 w-auto dark:hidden"
                                />
                                <img
                                    :src="appStoreBadgeWhite"
                                    alt=""
                                    width="120"
                                    height="40"
                                    class="hidden h-10 w-auto dark:block"
                                    aria-hidden="true"
                                />
                            </a>
                        </div>

                        <section>
                            <h2 class="mb-2 font-medium">Designed to be clear</h2>
                            <p class="text-[#706f6c] dark:text-[#A1A09A]">
                                MaiExpenses is built with a modern,
                                distraction-free interface that works beautifully
                                on iPhone and iPad. Charts and category chips make
                                patterns obvious at a glance—so you can adjust
                                before the month gets away from you.
                            </p>
                        </section>

                        <section>
                            <h2 class="mb-3 font-medium">Who it’s for</h2>
                            <ul class="flex flex-col gap-2 text-[#706f6c] dark:text-[#A1A09A]">
                                <li
                                    v-for="item in audience"
                                    :key="item"
                                    class="flex gap-2"
                                >
                                    <span
                                        class="shrink-0 text-[#f53003] dark:text-[#FF4433]"
                                        aria-hidden="true"
                                    >
                                        •
                                    </span>
                                    <span>{{ item }}</span>
                                </li>
                            </ul>
                        </section>

                        <section
                            class="rounded-md bg-white/70 p-4 dark:bg-[#161615]/80"
                        >
                            <h2 class="mb-2 font-medium">Your data, your account</h2>
                            <p class="text-[#706f6c] dark:text-[#A1A09A]">
                                Sign in securely to access your expenses across
                                sessions. Your records are tied to your account
                                and synced through our backend—so your history is
                                there when you need it.
                            </p>
                        </section>

                        <p class="font-medium text-[#706f6c] dark:text-[#A1A09A]">
                            Start tracking smarter today—available now on the App
                            Store.
                        </p>
                    </div>
                </div>
            </main>
        </div>

        <div class="hidden h-14.5 lg:block" />
    </div>
</template>
