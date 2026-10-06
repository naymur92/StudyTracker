// Display metadata for weekly plans. Gear hours and descriptions come from the
// API (`gear_options`), because they depend on the study profile and off days.
export const gears = [
    { value: 'green', label: 'Green', chip: 'bg-green-600 text-white', soft: 'bg-green-50 border-green-300' },
    { value: 'yellow', label: 'Yellow', chip: 'bg-yellow-500 text-white', soft: 'bg-yellow-50 border-yellow-300' },
    { value: 'red', label: 'Red', chip: 'bg-red-600 text-white', soft: 'bg-red-50 border-red-300' },
]

/** Gear styling merged with the API's gear option (label, description, hours). */
export const gearsWithOptions = (options = []) =>
    gears.map((g) => {
        const option = options.find((o) => o.gear === g.value) || {}
        return {
            ...g,
            label: option.label || g.label,
            desc: option.description || '',
            hours: option.hours !== undefined ? `≈ ${option.hours} h` : '',
        }
    })

/** Day-type wording for a study profile. */
export const dayTerms = (profile) => (profile === 'student'
    ? { workday: 'class day', off: 'free day', workdays: 'class days', offs: 'free days' }
    : { workday: 'office day', off: 'off day', workdays: 'office days', offs: 'off days' })

export const gearByValue = (value) => gears.find((g) => g.value === value) || gears[0]

export const slotLabels = {
    morning: 'Morning deep block',
    class_recap: 'Class recap',
    deep: 'Deep block',
    block_a: 'Block A',
    block_b: 'Block B',
    review: 'Reviews',
    minor: 'Minor slot',
    other: 'Other',
}

export const laneStyles = {
    major: 'bg-primary-100 text-primary-800',
    minor: 'bg-indigo-100 text-indigo-800',
    review: 'bg-amber-100 text-amber-800',
    work: 'bg-slate-200 text-slate-800',
}

export const statuses = [
    { value: 'done', label: 'Done', short: '✓', style: 'bg-success-600 text-white' },
    { value: 'partial', label: 'Partial', short: '½', style: 'bg-amber-500 text-white' },
    { value: 'missed', label: 'Missed', short: '✗', style: 'bg-gray-500 text-white' },
    { value: 'red', label: 'Red day', short: 'R', style: 'bg-red-600 text-white' },
]
