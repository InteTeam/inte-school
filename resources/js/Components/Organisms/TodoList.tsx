import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
    arrayMove,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Badge } from '@/Components/ui/badge';

export interface TodoItem {
    id: string;
    title: string;
    is_completed: boolean;
    is_custom: boolean;
    sort_order: number;
    deadline_at: string | null;
}

interface SortableItemProps {
    item: TodoItem;
    onToggle: (id: string) => void;
}

function SortableItem({ item, onToggle }: SortableItemProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-muted/40"
        >
            {/* Drag handle */}
            <span
                {...attributes}
                {...listeners}
                className="text-muted-foreground cursor-grab active:cursor-grabbing text-lg leading-none select-none"
                aria-label="Drag to reorder"
            >
                ⠿
            </span>

            <input
                type="checkbox"
                checked={item.is_completed}
                onChange={() => onToggle(item.id)}
                className="rounded"
            />

            <span className={`flex-1 text-sm ${item.is_completed ? 'line-through text-muted-foreground' : ''}`}>
                {item.title}
            </span>

            {!item.is_custom && (
                <Badge variant="outline" className="text-xs py-0">template</Badge>
            )}

            {item.deadline_at && (
                <span className="text-xs text-muted-foreground">
                    Due {new Date(item.deadline_at).toLocaleDateString()}
                </span>
            )}
        </div>
    );
}

interface Props {
    taskId: string;
    items: TodoItem[];
}

export default function TodoList({ taskId, items: initialItems }: Props) {
    const [items, setItems] = useState(initialItems);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    function handleToggle(itemId: string) {
        router.post(
            route('teacher.tasks.items.toggle'),
            { item_id: itemId },
            {
                preserveState: false,
                onSuccess: () => {
                    setItems(prev =>
                        prev.map(i =>
                            i.id === itemId ? { ...i, is_completed: !i.is_completed } : i,
                        ),
                    );
                },
            },
        );
    }

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setItems(prev => {
                const oldIndex = prev.findIndex(i => i.id === active.id);
                const newIndex = prev.findIndex(i => i.id === over.id);
                const reordered = arrayMove(prev, oldIndex, newIndex);

                // Persist reorder
                router.post(
                    route('teacher.tasks.items.reorder'),
                    { task_id: taskId, ordered_ids: reordered.map(i => i.id) },
                    { preserveState: true },
                );

                return reordered;
            });
        }
    }

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
        >
            <SortableContext items={items.map(i => i.id)} strategy={verticalListSortingStrategy}>
                <div className="space-y-0.5">
                    {items.map(item => (
                        <SortableItem key={item.id} item={item} onToggle={handleToggle} />
                    ))}
                    {items.length === 0 && (
                        <p className="text-sm text-muted-foreground px-3 py-4 text-center">
                            No items. Add one or apply a template.
                        </p>
                    )}
                </div>
            </SortableContext>
        </DndContext>
    );
}
