// Display metadata for weekly plans. Approximate hours come from the gear
// templates in config/study.php with two off days per week.
export const gears = [
    { value: 'green', label: 'Green', hours: '≈ 19 h', desc: 'Normal week: everything in the template', chip: 'bg-green-600 text-white', soft: 'bg-green-50 border-green-300' },
    { value: 'yellow', label: 'Yellow', hours: '≈ 11 h', desc: 'Busy week: mornings, short reviews, one Block A', chip: 'bg-yellow-500 text-white', soft: 'bg-yellow-50 border-yellow-300' },
    { value: 'red', label: 'Red', hours: '≈ 2 h', desc: 'Eid, illness, travel: 20 minutes of reviews a day', chip: 'bg-red-600 text-white', soft: 'bg-red-50 border-red-300' },
]

export const gearByValue = (value) => gears.find((g) => g.value === value) || gears[0]

export const slotLabels = {
    morning: 'Morning deep block',
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
