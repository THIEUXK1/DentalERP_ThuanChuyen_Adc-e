import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useFlash() {
    const page = usePage();
    return {
        success: computed(() => page.props.flash?.success),
        error:   computed(() => page.props.flash?.error),
        warning: computed(() => page.props.flash?.warning),
    };
}
