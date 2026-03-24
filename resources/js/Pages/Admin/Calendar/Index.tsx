import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

interface CalendarItem {
    id: string;
    name: string;
    type: string;
    color: string | null;
    is_public: boolean;
}

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
    calendars: CalendarItem[];
    events: CalendarEventItem[];
    year: number;
    month: number;
}

const TYPE_LABELS: Record<string, string> = {
    internal: 'Internal',
    external: 'External',
    department: 'Department',
    holiday: 'Holiday',
};

export default function AdminCalendarIndex({ calendars, events, year, month }: Props) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [selectedDate, setSelectedDate] = useState<string | null>(null);

    function handleDateClick(info: { dateStr: string }) {
        setSelectedDate(info.dateStr);
        setShowCreateModal(true);
    }

    function handleMonthChange(info: { start: Date }) {
        router.get(route('calendar.index'), {
            year: info.start.getFullYear(),
            month: info.start.getMonth() + 1,
        }, { preserveState: true });
    }

    return (
        <SchoolLayout>
            <Head title="Calendar" />

            <div className="flex gap-6">
                {/* Sidebar */}
                <div className="w-56 shrink-0">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="text-sm font-semibold">Calendars</h2>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setShowCreateModal(true)}
                        >
                            +
                        </Button>
                    </div>

                    <ul className="space-y-1">
                        {calendars.map(cal => (
                            <li key={cal.id} className="flex items-center gap-2 text-sm py-1">
                                <span
                                    className="h-3 w-3 rounded-full shrink-0"
                                    style={{ backgroundColor: cal.color ?? '#6366f1' }}
                                />
                                <span className="truncate">{cal.name}</span>
                                {cal.is_public && (
                                    <Badge variant="outline" className="text-xs py-0 px-1 ml-auto">public</Badge>
                                )}
                            </li>
                        ))}
                        {calendars.length === 0 && (
                            <li className="text-xs text-muted-foreground">No calendars yet.</li>
                        )}
                    </ul>

                    <div className="mt-4">
                        <h3 className="text-xs text-muted-foreground mb-2 uppercase tracking-wide">Types</h3>
                        {Object.entries(TYPE_LABELS).map(([type, label]) => (
                            <div key={type} className="flex items-center gap-1.5 text-xs py-0.5">
                                <span className="h-2 w-2 rounded-full bg-muted" />
                                {label}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Calendar */}
                <div className="flex-1 min-w-0">
                    <FullCalendar
                        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
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
                        dateClick={handleDateClick}
                        datesSet={handleMonthChange}
                        height="auto"
                        firstDay={1}
                    />
                </div>
            </div>
        </SchoolLayout>
    );
}
