@props([
    'message' => null,
    'type' => 'success',
    'dismissible' => true,
    'wireDismiss' => "\$set('feedbackMessage', null)",
])

@if($message)
    <div 
        x-data="{ 
            msg: @js($message), 
            variant: @js($type),
            show() {
                if (window.swalToast) {
                    window.swalToast(this.msg, this.variant);
                } else {
                    document.addEventListener('DOMContentLoaded', () => {
                        window.swalToast?.(this.msg, this.variant);
                    }, { once: true });
                }
            }
        }" 
        x-init="show()"
        class="hidden"
    ></div>
@endif

