import { Head, router } from '@inertiajs/react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import ParentLayout from '@/layouts/ParentLayout';

interface CalendarEventItem {
    id: string;
    title: string;
    start: string;
    end: string;
    allDay: boolean;
    backgroundColor: string | null;
    calendarName: string;
    location?: string;
}

interface Props {
    events: CalendarEventItem[];
    year: number;
    month: number;
}

export default function ParentCalendarIndex({ events, year, month }: Props) {
    function handleMonthChange(info: { start: Date }) {
        router.get(route('parent.calendar.index'), {
            year: info.start.getFullYear(),
            month: info.start.getMonth() + 1,
        }, { preserveState: true });
    }

    return (
        <ParentLayout>
            <Head title="School Calendar" />

            <div className="px-4 pt-4 pb-2">
                <h1 className="text-xl font-bold">School Calendar</h1>
                <p className="text-sm text-muted-foreground">School events and holidays</p>
            </div>

            <div className="px-4 pb-6">
                <FullCalendar
                    plugins={[dayGridPlugin, listPlugin]}
                    initialView="listMonth"
                    headerToolbar={{
                        left: 'prev,next',
                        center: 'title',
                        right: 'dayGridMonth,listMonth',
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
            </div>
        </ParentLayout>
    );
}
