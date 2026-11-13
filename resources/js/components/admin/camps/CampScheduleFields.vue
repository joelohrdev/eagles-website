<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import MoneyInput from '@/components/admin/MoneyInput.vue';
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import TimeField from '@/components/TimeField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { CampScheduleForm } from '@/types/camps';

/**
 * Age groups, sessions, days and per-group times for a camp. Every value is a
 * named input, so the surrounding <Form> submits it as nested arrays.
 * Times point at age groups by `key`, which new groups get before they have an id.
 */
const props = defineProps<{
    schedule?: CampScheduleForm | null;
    bundlePrice?: string | null;
    capacity?: number | null;
    errors: Record<string, string>;
}>();

type TimeRow = { starts_at: string; ends_at: string };
type DayRow = {
    uid: string;
    id: number | null;
    date: string;
    label: string;
    times: Record<string, TimeRow>;
};
type SessionRow = {
    uid: string;
    id: number | null;
    name: string;
    description: string;
    price: string;
    day_price: string;
    days: DayRow[];
};
type GroupRow = {
    key: string;
    id: number | null;
    name: string;
    capacity: string;
};

/** Times for a camp without age groups are stored under this key. */
const NO_GROUP = '_';

let counter = 0;
const uid = (prefix: string) => `${prefix}-new-${++counter}`;

const state = reactive<{ groups: GroupRow[]; sessions: SessionRow[] }>({
    groups: (props.schedule?.age_groups ?? []).map((group) => ({
        key: group.key,
        id: group.id,
        name: group.name,
        capacity: group.capacity?.toString() ?? '',
    })),
    sessions: (props.schedule?.sessions ?? []).map((session) => ({
        uid: `session-${session.id}`,
        id: session.id,
        name: session.name,
        description: session.description ?? '',
        price: session.price ?? '',
        day_price: session.day_price ?? '',
        days: session.days.map((day) => ({
            uid: `day-${day.id}`,
            id: day.id,
            date: day.date ?? '',
            label: day.label ?? '',
            times: Object.fromEntries(
                day.times.map((time) => [
                    time.age_group_key ?? NO_GROUP,
                    {
                        starts_at: time.starts_at ?? '',
                        ends_at: time.ends_at ?? '',
                    },
                ]),
            ),
        })),
    })),
});

if (state.sessions.length === 0) {
    addSession();
}

/** Columns of times: one per age group, or a single unnamed one. */
const timeColumns = computed(() =>
    state.groups.length === 0
        ? [{ key: NO_GROUP, name: 'Time' }]
        : state.groups.map((group) => ({
              key: group.key,
              name: group.name || 'New age group',
          })),
);

function blankDay(): DayRow {
    return { uid: uid('day'), id: null, date: '', label: '', times: {} };
}

function addSession() {
    state.sessions.push({
        uid: uid('session'),
        id: null,
        name: '',
        description: '',
        price: '',
        day_price: '',
        days: [blankDay()],
    });
}

function addDay(session: SessionRow) {
    const previous = session.days.at(-1);
    const day = blankDay();

    /** Copy the previous day's times: most camps run at the same times every day. */
    if (previous) {
        day.times = JSON.parse(JSON.stringify(previous.times));
    }

    session.days.push(day);
}

function addGroup() {
    const key = uid('group');
    const first = state.groups.length === 0;
    state.groups.push({ key, id: null, name: '', capacity: '' });

    /** The first group takes over the times entered before there were groups. */
    for (const session of state.sessions) {
        for (const day of session.days) {
            if (first && day.times[NO_GROUP]) {
                day.times[key] = day.times[NO_GROUP];
                delete day.times[NO_GROUP];
            }
        }
    }
}

function removeGroup(index: number) {
    const [removed] = state.groups.splice(index, 1);

    for (const session of state.sessions) {
        for (const day of session.days) {
            const times = day.times[removed.key];
            delete day.times[removed.key];

            if (state.groups.length === 0 && times) {
                day.times[NO_GROUP] = times;
            }
        }
    }
}

/** Make sure every day has a time row for every column before it renders. */
watch(
    () => [state.sessions, timeColumns.value],
    () => {
        for (const session of state.sessions) {
            for (const day of session.days) {
                for (const column of timeColumns.value) {
                    day.times[column.key] ??= { starts_at: '', ends_at: '' };
                }
            }
        }
    },
    { deep: true, immediate: true },
);

function timeFor(day: DayRow, key: string): TimeRow {
    return day.times[key] ?? { starts_at: '', ends_at: '' };
}

function error(path: string): string | undefined {
    return props.errors[path];
}
</script>

<template>
    <div class="space-y-8">
        <section class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Age groups</h3>
                <p class="text-xs text-muted-foreground">
                    Optional. Each group gets its own time every day and its own
                    limit per day. Leave empty when everyone trains together.
                </p>
            </div>

            <div
                v-for="(group, index) in state.groups"
                :key="group.key"
                class="grid items-start gap-3 sm:grid-cols-[1fr_10rem_auto]"
            >
                <input
                    type="hidden"
                    :name="`age_groups[${index}][key]`"
                    :value="group.key"
                />
                <input
                    v-if="group.id"
                    type="hidden"
                    :name="`age_groups[${index}][id]`"
                    :value="group.id"
                />
                <div class="grid gap-1">
                    <Label :for="`group-name-${group.key}`" class="sr-only"
                        >Age group name</Label
                    >
                    <Input
                        :id="`group-name-${group.key}`"
                        v-model="group.name"
                        :name="`age_groups[${index}][name]`"
                        placeholder="8–12U"
                        required
                    />
                    <InputError :message="error(`age_groups.${index}.name`)" />
                </div>
                <div class="grid gap-1">
                    <Label :for="`group-cap-${group.key}`" class="sr-only"
                        >Players per day</Label
                    >
                    <Input
                        :id="`group-cap-${group.key}`"
                        v-model="group.capacity"
                        :name="`age_groups[${index}][capacity]`"
                        type="number"
                        min="1"
                        placeholder="No limit"
                    />
                    <InputError
                        :message="error(`age_groups.${index}.capacity`)"
                    />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="`Remove ${group.name || 'age group'}`"
                    @click="removeGroup(index)"
                    ><Trash2 class="size-4"
                /></Button>
            </div>
            <InputError :message="error('age_groups')" />

            <div class="flex flex-wrap items-end gap-4">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addGroup"
                    ><Plus class="size-4" /> Add age group</Button
                >
                <div v-if="state.groups.length === 0" class="grid gap-1">
                    <Label for="capacity" class="text-xs"
                        >Players per day
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="capacity"
                        name="capacity"
                        type="number"
                        min="1"
                        class="w-40"
                        :default-value="capacity ?? ''"
                        placeholder="No limit"
                    />
                    <InputError :message="error('capacity')" />
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Sessions</h3>
                <p class="text-xs text-muted-foreground">
                    A session is a set of days families book together, like
                    Pitching on Nov 21 and 23. Add a single-day price to let
                    them book one day of it.
                </p>
            </div>
            <InputError :message="error('sessions')" />

            <div
                v-for="(session, s) in state.sessions"
                :key="session.uid"
                class="space-y-4 rounded-lg border p-4"
            >
                <input
                    v-if="session.id"
                    type="hidden"
                    :name="`sessions[${s}][id]`"
                    :value="session.id"
                />

                <div class="grid gap-4 sm:grid-cols-[1fr_9rem_9rem_auto]">
                    <div class="grid content-start gap-1">
                        <Label :for="`session-name-${session.uid}`"
                            >Session name</Label
                        >
                        <Input
                            :id="`session-name-${session.uid}`"
                            v-model="session.name"
                            :name="`sessions[${s}][name]`"
                            placeholder="Pitching"
                            required
                        />
                        <InputError :message="error(`sessions.${s}.name`)" />
                    </div>
                    <div class="grid content-start gap-1">
                        <Label :for="`session-price-${session.uid}`"
                            >Price (all days)</Label
                        >
                        <MoneyInput
                            :id="`session-price-${session.uid}`"
                            v-model="session.price"
                            :name="`sessions[${s}][price]`"
                            placeholder="125.00"
                            required
                        />
                        <InputError :message="error(`sessions.${s}.price`)" />
                    </div>
                    <div class="grid content-start gap-1">
                        <Label :for="`session-day-price-${session.uid}`"
                            >Single-day price</Label
                        >
                        <MoneyInput
                            :id="`session-day-price-${session.uid}`"
                            v-model="session.day_price"
                            :name="`sessions[${s}][day_price]`"
                            placeholder="Not offered"
                        />
                        <InputError
                            :message="error(`sessions.${s}.day_price`)"
                        />
                    </div>
                    <div class="flex items-end">
                        <Button
                            v-if="state.sessions.length > 1"
                            type="button"
                            variant="ghost"
                            size="icon"
                            :aria-label="`Remove ${session.name || 'session'}`"
                            @click="state.sessions.splice(s, 1)"
                            ><Trash2 class="size-4"
                        /></Button>
                    </div>
                </div>

                <div class="grid gap-1">
                    <Label :for="`session-description-${session.uid}`"
                        >Description
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Textarea
                        :id="`session-description-${session.uid}`"
                        v-model="session.description"
                        :name="`sessions[${s}][description]`"
                        rows="2"
                    />
                </div>

                <div class="space-y-3">
                    <InputError :message="error(`sessions.${s}.days`)" />
                    <div
                        v-for="(day, d) in session.days"
                        :key="day.uid"
                        class="space-y-3 rounded-md bg-muted/40 p-3"
                    >
                        <input
                            v-if="day.id"
                            type="hidden"
                            :name="`sessions[${s}][days][${d}][id]`"
                            :value="day.id"
                        />
                        <div class="grid gap-3 sm:grid-cols-[14rem_1fr_auto]">
                            <div class="grid content-start gap-1">
                                <Label :for="`day-date-${day.uid}`">Date</Label>
                                <DatePicker
                                    :id="`day-date-${day.uid}`"
                                    v-model="day.date"
                                    :name="`sessions[${s}][days][${d}][date]`"
                                    required
                                    :invalid="
                                        !!error(`sessions.${s}.days.${d}.date`)
                                    "
                                />
                                <InputError
                                    :message="
                                        error(`sessions.${s}.days.${d}.date`)
                                    "
                                />
                            </div>
                            <div class="grid content-start gap-1">
                                <Label :for="`day-label-${day.uid}`"
                                    >Label
                                    <span class="text-muted-foreground"
                                        >(optional)</span
                                    ></Label
                                >
                                <Input
                                    :id="`day-label-${day.uid}`"
                                    v-model="day.label"
                                    :name="`sessions[${s}][days][${d}][label]`"
                                    placeholder="Fielding"
                                />
                            </div>
                            <div class="flex items-end">
                                <Button
                                    v-if="session.days.length > 1"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove day"
                                    @click="session.days.splice(d, 1)"
                                    ><Trash2 class="size-4"
                                /></Button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <fieldset
                                v-for="(column, t) in timeColumns"
                                :key="column.key"
                                class="grid gap-1"
                            >
                                <legend class="mb-1 text-xs font-medium">
                                    {{ column.name }}
                                </legend>
                                <input
                                    type="hidden"
                                    :name="`sessions[${s}][days][${d}][times][${t}][age_group_key]`"
                                    :value="
                                        column.key === '_' ? '' : column.key
                                    "
                                />
                                <div class="flex items-center gap-2">
                                    <TimeField
                                        v-model="
                                            timeFor(day, column.key).starts_at
                                        "
                                        class="flex-1"
                                        :name="`sessions[${s}][days][${d}][times][${t}][starts_at]`"
                                        :label="`${column.name} start time`"
                                        :invalid="
                                            !!error(
                                                `sessions.${s}.days.${d}.times.${t}.starts_at`,
                                            )
                                        "
                                    />
                                    <span class="text-muted-foreground"
                                        >to</span
                                    >
                                    <TimeField
                                        v-model="
                                            timeFor(day, column.key).ends_at
                                        "
                                        class="flex-1"
                                        :name="`sessions[${s}][days][${d}][times][${t}][ends_at]`"
                                        :label="`${column.name} end time`"
                                        :invalid="
                                            !!error(
                                                `sessions.${s}.days.${d}.times.${t}.ends_at`,
                                            )
                                        "
                                    />
                                </div>
                                <InputError
                                    :message="
                                        error(
                                            `sessions.${s}.days.${d}.times.${t}.starts_at`,
                                        ) ??
                                        error(
                                            `sessions.${s}.days.${d}.times.${t}.ends_at`,
                                        )
                                    "
                                />
                            </fieldset>
                        </div>
                        <InputError
                            :message="error(`sessions.${s}.days.${d}.times`)"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addDay(session)"
                        ><Plus class="size-4" /> Add day</Button
                    >
                </div>
            </div>

            <Button type="button" variant="outline" @click="addSession"
                ><Plus class="size-4" /> Add session</Button
            >
        </section>

        <section v-if="state.sessions.length > 1" class="grid gap-2 sm:w-64">
            <Label for="bundle_price"
                >Price for all sessions
                <span class="text-muted-foreground">(optional)</span></Label
            >
            <MoneyInput
                id="bundle_price"
                name="bundle_price"
                :default-value="bundlePrice"
                placeholder="225.00"
            />
            <p class="text-xs text-muted-foreground">
                Used when a family books every day of every session. Must be
                less than the sessions added up. A coupon comes off this price.
            </p>
            <InputError :message="error('bundle_price')" />
        </section>
    </div>
</template>
