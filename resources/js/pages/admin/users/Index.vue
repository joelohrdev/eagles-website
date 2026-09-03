<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { RefreshCw, Send } from '@lucide/vue';
import { computed } from 'vue';
import InvitationController from '@/actions/App/Http/Controllers/Admin/InvitationController';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { index as usersIndex } from '@/routes/admin/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string | null;
};
type InvitationRow = {
    id: number;
    email: string;
    role: string;
    inviter: string | null;
    expires_at: string;
    created_at: string | null;
};
type RoleOption = { value: string; label: string };

const props = defineProps<{
    users: UserRow[];
    invitations: InvitationRow[];
    roles: RoleOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users & Invites', href: usersIndex() }],
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);
const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);

function updateRole(user: UserRow, role: unknown) {
    if (typeof role !== 'string' || role === user.role) {
        return;
    }

    router.patch(
        UserController.update.url(user.id),
        { role },
        { preserveScroll: true },
    );
}

function resend(invitation: InvitationRow) {
    router.post(
        InvitationController.resend.url(invitation.id),
        {},
        { preserveScroll: true },
    );
}

function roleLabel(value: string): string {
    return props.roles.find((r) => r.value === value)?.label ?? value;
}
</script>

<template>
    <Head title="Users & Invites" />

    <div class="flex flex-col gap-8 p-4 md:p-6">
        <Heading
            title="Users & Invites"
            description="Manage who can sign in to the admin. Access is invite-only."
        />

        <InputError :message="errors.user" />

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-1">
                <CardHeader>
                    <CardTitle>Invite someone</CardTitle>
                    <CardDescription
                        >They'll receive an email with a link to create their
                        account. Links expire after 7 days.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="InvitationController.store.form()"
                        class="grid gap-4"
                        reset-on-success
                        v-slot="{ errors: formErrors, processing }"
                    >
                        <div class="grid content-start gap-2">
                            <Label for="invite_email">Email</Label>
                            <Input
                                id="invite_email"
                                name="email"
                                type="email"
                                required
                                placeholder="coach@example.com"
                            />
                            <InputError :message="formErrors.email" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="invite_role">Role</Label>
                            <Select name="role" default-value="staff">
                                <SelectTrigger id="invite_role"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="role in roles"
                                        :key="role.value"
                                        :value="role.value"
                                        >{{ role.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="formErrors.role" />
                            <p class="text-xs text-muted-foreground">
                                <strong>Staff</strong> manage content.
                                <strong>Admins</strong> also manage users, site
                                settings, and SEO defaults.
                            </p>
                        </div>
                        <Button type="submit" :disabled="processing"
                            ><Send class="size-4" /> Send invitation</Button
                        >
                    </Form>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Users</CardTitle>
                    <CardDescription
                        >{{ users.length }}
                        {{
                            users.length === 1 ? 'account' : 'accounts'
                        }}</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Joined</TableHead>
                                <TableHead class="w-24" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users" :key="user.id">
                                <TableCell class="font-medium">
                                    {{ user.name }}
                                    <span
                                        v-if="user.id === currentUserId"
                                        class="ml-1 text-xs text-muted-foreground"
                                        >(you)</span
                                    >
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{
                                    user.email
                                }}</TableCell>
                                <TableCell>
                                    <StatusBadge
                                        v-if="user.id === currentUserId"
                                        :status="user.role"
                                        :label="roleLabel(user.role)"
                                    />
                                    <Select
                                        v-else
                                        :model-value="user.role"
                                        @update:model-value="
                                            updateRole(user, $event)
                                        "
                                    >
                                        <SelectTrigger
                                            class="h-8 w-32"
                                            :aria-label="`Role for ${user.name}`"
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="role in roles"
                                                :key="role.value"
                                                :value="role.value"
                                                >{{ role.label }}</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{
                                    formatDate(user.created_at)
                                }}</TableCell>
                                <TableCell class="text-right">
                                    <ConfirmDelete
                                        v-if="user.id !== currentUserId"
                                        :href="
                                            UserController.destroy.url(user.id)
                                        "
                                        size="icon-sm"
                                        label="Remove"
                                        title="Remove this user?"
                                        :description="`${user.name} will no longer be able to sign in.`"
                                    />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <InputError class="mt-2" :message="errors.role" />
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Pending invitations</CardTitle>
                <CardDescription
                    >Invitations that haven't been accepted
                    yet.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <EmptyState
                    v-if="invitations.length === 0"
                    title="No pending invitations"
                    description="Invite a coach or staff member using the form above."
                />
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Invited by</TableHead>
                            <TableHead>Expires</TableHead>
                            <TableHead class="w-40" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="invitation in invitations"
                            :key="invitation.id"
                        >
                            <TableCell class="font-medium">{{
                                invitation.email
                            }}</TableCell>
                            <TableCell
                                ><StatusBadge
                                    :status="invitation.role"
                                    :label="roleLabel(invitation.role)"
                            /></TableCell>
                            <TableCell class="text-muted-foreground">{{
                                invitation.inviter ?? '—'
                            }}</TableCell>
                            <TableCell class="text-muted-foreground">{{
                                formatDate(invitation.expires_at)
                            }}</TableCell>
                            <TableCell class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="resend(invitation)"
                                >
                                    <RefreshCw class="size-4" /> Resend
                                </Button>
                                <ConfirmDelete
                                    :href="
                                        InvitationController.destroy.url(
                                            invitation.id,
                                        )
                                    "
                                    label="Revoke"
                                    title="Revoke this invitation?"
                                    description="The link in the email will stop working."
                                />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
