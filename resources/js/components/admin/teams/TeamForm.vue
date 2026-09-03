<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/Admin/TeamController';
import FormTabs from '@/components/admin/FormTabs.vue';
import ImageUpload from '@/components/admin/ImageUpload.vue';
import SeoFields from '@/components/admin/SeoFields.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { CoachOption, SeoMetaProp, Team } from '@/types/teams';

const props = withDefaults(
    defineProps<{
        team?: Team | null;
        seo?: SeoMetaProp;
        coaches: CoachOption[];
    }>(),
    { team: null, seo: null },
);

const isActive = ref(props.team?.is_active ?? true);
const coachId = ref(
    props.team?.coach_id ? String(props.team.coach_id) : 'none',
);

const formProps = props.team
    ? TeamController.update.form(props.team)
    : TeamController.store.form();
</script>

<template>
    <Form v-bind="formProps" class="space-y-8" v-slot="{ errors, processing }">
        <FormTabs
            :errors="errors"
            :tabs="[{ value: 'details' }, { value: 'seo', prefix: 'seo' }]"
            class="w-full"
        >
            <TabsList>
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="seo">SEO &amp; Sharing</TabsTrigger>
            </TabsList>

            <TabsContent value="details" class="mt-6 space-y-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="grid content-start gap-2">
                        <Label for="name">Team name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="team?.name ?? ''"
                            required
                            placeholder="Eagles 12U Navy"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="division">Division / age group</Label>
                        <Input
                            id="division"
                            name="division"
                            :default-value="team?.division ?? ''"
                            required
                            placeholder="12U"
                        />
                        <InputError :message="errors.division" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="season">Season</Label>
                        <Input
                            id="season"
                            name="season"
                            :default-value="team?.season ?? ''"
                            placeholder="2026"
                        />
                        <InputError :message="errors.season" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="coach_id">Head coach</Label>
                        <Select v-model="coachId">
                            <SelectTrigger id="coach_id" class="w-full">
                                <SelectValue placeholder="No coach assigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"
                                    >No coach assigned</SelectItem
                                >
                                <SelectItem
                                    v-for="coach in coaches"
                                    :key="coach.id"
                                    :value="String(coach.id)"
                                >
                                    {{ coach.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input
                            type="hidden"
                            name="coach_id"
                            :value="coachId === 'none' ? '' : coachId"
                        />
                        <InputError :message="errors.coach_id" />
                    </div>
                </div>

                <div class="grid content-start gap-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows="5"
                        :default-value="team?.description ?? ''"
                        placeholder="Tell families about this team."
                    />
                    <InputError :message="errors.description" />
                </div>

                <ImageUpload
                    name="photo"
                    label="Team photo"
                    remove-name="remove_photo"
                    :current-url="team?.photo_url ?? null"
                    :error="errors.photo"
                />

                <div class="flex items-center gap-3">
                    <Switch id="is_active" v-model="isActive" />
                    <input
                        type="hidden"
                        name="is_active"
                        :value="isActive ? 1 : 0"
                    />
                    <Label for="is_active">Show on the public Teams page</Label>
                </div>
            </TabsContent>

            <TabsContent value="seo" class="mt-6">
                <SeoFields
                    :seo="seo"
                    :errors="errors"
                    :fallback="{
                        title: team?.name ?? 'Team name',
                        description: team?.description ?? null,
                        image_url: team?.photo_url ?? null,
                    }"
                />
            </TabsContent>
        </FormTabs>

        <div class="flex items-center gap-3 border-t pt-6">
            <Button :disabled="processing">{{
                team ? 'Save changes' : 'Create team'
            }}</Button>
        </div>
    </Form>
</template>
