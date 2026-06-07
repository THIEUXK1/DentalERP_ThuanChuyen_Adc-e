import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermission() {
    const page = usePage();

    const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
    const roles       = computed(() => page.props.auth?.user?.roles ?? []);

    function hasPermission(name) {
        return permissions.value.includes(name);
    }

    function hasRole(name) {
        return roles.value.includes(name);
    }

    function hasAnyRole(...names) {
        return names.some(n => roles.value.includes(n));
    }

    return { hasPermission, hasRole, hasAnyRole };
}
