export type ShiftSelectionMode = 'manual' | 'range';

export type ShiftAssignmentSelectionState = {
    selectionMode: ShiftSelectionMode;
    rangeAnchor: string | null;
    assignDates: Set<string>;
    replaceDayIds: Set<number>;
    removeDayIds: Set<number>;
};

export type ShiftAssignmentSelectionAction =
    | { type: 'set-mode'; mode: ShiftSelectionMode }
    | { type: 'set-range-anchor'; date: string | null }
    | { type: 'toggle-assign-date'; date: string }
    | { type: 'add-assign-dates'; dates: string[] }
    | { type: 'toggle-replace-day'; dayId: number }
    | { type: 'toggle-remove-day'; dayId: number }
    | { type: 'reset' };

export function emptyShiftAssignmentSelection(): ShiftAssignmentSelectionState {
    return {
        selectionMode: 'manual',
        rangeAnchor: null,
        assignDates: new Set<string>(),
        replaceDayIds: new Set<number>(),
        removeDayIds: new Set<number>(),
    };
}

export function shiftAssignmentSelectionReducer(
    state: ShiftAssignmentSelectionState,
    action: ShiftAssignmentSelectionAction,
): ShiftAssignmentSelectionState {
    if (action.type === 'reset') {
        return emptyShiftAssignmentSelection();
    }

    if (action.type === 'set-mode') {
        return {
            ...state,
            selectionMode: action.mode,
            rangeAnchor: null,
        };
    }

    if (action.type === 'set-range-anchor') {
        return { ...state, rangeAnchor: action.date };
    }

    if (action.type === 'toggle-assign-date') {
        const assignDates = new Set(state.assignDates);

        if (assignDates.has(action.date)) {
            assignDates.delete(action.date);
        } else {
            assignDates.add(action.date);
        }

        return { ...state, assignDates, rangeAnchor: null };
    }

    if (action.type === 'add-assign-dates') {
        const assignDates = new Set(state.assignDates);

        for (const date of action.dates) {
            assignDates.add(date);
        }

        return { ...state, assignDates, rangeAnchor: null };
    }

    if (action.type === 'toggle-replace-day') {
        const replaceDayIds = new Set(state.replaceDayIds);
        const removeDayIds = new Set(state.removeDayIds);

        removeDayIds.delete(action.dayId);

        if (replaceDayIds.has(action.dayId)) {
            replaceDayIds.delete(action.dayId);
        } else {
            replaceDayIds.add(action.dayId);
        }

        return { ...state, replaceDayIds, removeDayIds, rangeAnchor: null };
    }

    const removeDayIds = new Set(state.removeDayIds);
    const replaceDayIds = new Set(state.replaceDayIds);

    replaceDayIds.delete(action.dayId);

    if (removeDayIds.has(action.dayId)) {
        removeDayIds.delete(action.dayId);
    } else {
        removeDayIds.add(action.dayId);
    }

    return { ...state, replaceDayIds, removeDayIds, rangeAnchor: null };
}

export function hasPendingShiftChanges(
    state: ShiftAssignmentSelectionState,
): boolean {
    return (
        state.assignDates.size > 0 ||
        state.replaceDayIds.size > 0 ||
        state.removeDayIds.size > 0
    );
}
