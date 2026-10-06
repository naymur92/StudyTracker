import { format, addDays } from 'date-fns'

/** Today's date in the browser's local timezone (YYYY-MM-DD), not UTC. */
export const todayLocal = () => format(new Date(), 'yyyy-MM-dd')

/** Shift a YYYY-MM-DD date by N days, staying in local time. */
export const shiftDate = (date, days) => format(addDays(new Date(`${date}T00:00:00`), days), 'yyyy-MM-dd')

/** Parse a YYYY-MM-DD string as a local date. */
export const parseLocalDate = (date) => new Date(`${date}T00:00:00`)
