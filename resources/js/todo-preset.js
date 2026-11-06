/**
 * Choosing a frequent ToDo (PROJ-70) fills in its usual settings: waiting for an event, informing the customer,
 * what is waited for and the draft of the later reply. Without a choice, text that still equals the previous
 * preset's text is removed again, text the employee wrote stays.
 *
 * @param {object} todo The ToDo being edited (preset, due_in, waiting_for, notify_customer, notify_hint).
 * @param {Array} presets The frequent ToDos from the server.
 * @param {string} previous The label that was chosen before.
 */
export default function applyTodoPreset(todo, presets, previous) {
    const next = presets.find((preset) => preset.label === todo.preset);
    const old = presets.find((preset) => preset.label === previous);

    if (!next) {
        if (old && todo.waiting_for === old.waiting_for) { todo.waiting_for = ''; }
        if (old && todo.notify_hint === old.hint) { todo.notify_hint = ''; }
        return;
    }

    if (next.waiting) { todo.due_in = 'waiting'; } else if (todo.due_in === 'waiting' && old?.waiting) { todo.due_in = ''; }
    todo.waiting_for = next.waiting_for;
    todo.notify_customer = next.notify;
    todo.notify_hint = next.hint;
}
