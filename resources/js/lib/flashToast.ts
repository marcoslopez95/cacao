import { router } from '@inertiajs/vue3';
import { useToast  } from '@/components/feedback/useToast';
import type {ToastVariant} from '@/components/feedback/useToast';
import type { FlashToast } from '@/types/ui';

const TYPE_MAP: Record<FlashToast['type'], ToastVariant> = {
    success: 'success',
    info: 'info',
    warning: 'warning',
    error: 'danger',
};

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        const { toast } = useToast();
        toast({ message: data.message, variant: TYPE_MAP[data.type] });
    });
}
