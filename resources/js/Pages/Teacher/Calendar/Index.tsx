import { Head, router } from '@inertiajs/react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import SchoolLayout from '@/layouts/SchoolLayout';

interface CalendarEventItem {
    id: string;
    title: string;
    start: string;
    end: string;
    allDay: boolean;
    backgroundColor: string | null;
    calendarName: string;
}

interface Props {
    events: CalendarEventItem[];
    year: number;
    month: number;
}

export default function TeacherCalendarIndex({ events, year, month }: Props) {
    function handleMonthChange(info: { start: Date }) {
        router.get(route('calendar.index'), {
            year: info.start.getFullYear(),
            month: info.start.getMonth() + 1,
        }, { preserveState: true });
    }

    return (
        <SchoolLayout>
            <Head title="Calendar" />

            <FullCalendar
                plugins={[dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
                initialView="dayGridMonth"
                headerToolbar={{
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek',
                }}
                events={events.map(e => ({
                    id: e.id,
                    title: e.title,
                    start: e.start,
                    end: e.end,
                    allDay: e.allDay,
                    backgroundColor: e.backgroundColor ?? '#6366f1',
                    borderColor: e.backgroundColor ?? '#6366f1',
                }))}
                datesSet={handleMonthChange}
                height="auto"
                firstDay={1}
            />
        </SchoolLayout>
    );
}
