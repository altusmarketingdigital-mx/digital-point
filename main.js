document.addEventListener('DOMContentLoaded', () => {
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80, // Offset for fixed nav
                    behavior: 'smooth'
                });
            }
        });
    });

    // Interceptar el envío del formulario para usar AJAX (fetch) y no recargar la página
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Evitar que la página recargue

            // Cambiar texto a enviando...
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerText;
            submitBtn.innerText = 'Enviando...';
            submitBtn.disabled = true;

            const formData = new FormData(this);
            
            const nombre = formData.get('Nombre').trim();
            const contacto = formData.get('Contacto').trim();
            const mensaje = formData.get('Mensaje').trim();

            if (!nombre || !contacto || !mensaje) {
                showToast('Por favor, completa todos los campos para poder enviar tu mensaje.', false);
                submitBtn.innerText = originalBtnText;
                submitBtn.disabled = false;
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^\+?[\d\s-]{10,}$/;

            if (!emailRegex.test(contacto) && !phoneRegex.test(contacto)) {
                showToast('Por favor, ingresa un correo electrónico o número de WhatsApp válido.', false);
                submitBtn.innerText = originalBtnText;
                submitBtn.disabled = false;
                return;
            }

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    showToast('¡Tu mensaje ha sido enviado con éxito! Nos pondremos en contacto contigo pronto.', true);
                    contactForm.reset(); // Limpiar el formulario
                } else {
                    throw new Error('Server error');
                }
            })
            .catch(error => {
                showToast('Hubo un error de conexión, o estás probando el formulario de forma local sin PHP configurado.', false);
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.innerText = originalBtnText;
                submitBtn.disabled = false;
            });
        });
    }

    // Scroll reveal animation observer
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Apply reveal to sections
    document.querySelectorAll('section').forEach(section => {
        section.classList.add('opacity-0'); // Initial state for reveal
        observer.observe(section);
    });
});

// Legal Modal handlers
window.openLegalModal = function(type) {
    const modal = document.getElementById('legalModal');
    const panel = document.getElementById('legalModalPanel');
    const content = document.getElementById('legalModalContent');
    const title = document.getElementById('legalModalTitle');
    const template = document.getElementById(type === 'privacy' ? 'tplPrivacy' : 'tplTerms');
    
    // Set content and title
    title.innerText = type === 'privacy' ? 'Aviso de Privacidad' : 'Términos y Condiciones';
    content.innerHTML = template.innerHTML;
    
    // Show modal
    modal.classList.remove('opacity-0', 'pointer-events-none');
    panel.classList.remove('scale-95', 'opacity-0');
    panel.classList.add('scale-100', 'opacity-100');
    document.body.style.overflow = 'hidden'; // Prevent background scroll
}

window.closeLegalModal = function() {
    const modal = document.getElementById('legalModal');
    const panel = document.getElementById('legalModalPanel');
    
    // Hide modal
    modal.classList.add('opacity-0', 'pointer-events-none');
    panel.classList.add('scale-95', 'opacity-0');
    panel.classList.remove('scale-100', 'opacity-100');
    document.body.style.overflow = ''; // Restore scroll
}

// Toast Notification handlers
let toastTimeout;
window.showToast = function(message, isSuccess) {
    const toast = document.getElementById('toastNotification');
    const msgEl = document.getElementById('toastMessage');
    const iconSuccess = document.getElementById('toastIconSuccess');
    const iconError = document.getElementById('toastIconError');
    const iconContainer = document.getElementById('toastIconContainer');

    msgEl.innerText = message;

    if (isSuccess) {
        iconSuccess.classList.remove('hidden');
        iconError.classList.add('hidden');
        toast.classList.remove('border-red-500');
        toast.classList.add('border-green-500');
        iconContainer.classList.replace('text-red-500', 'text-green-500');
        iconContainer.classList.replace('bg-red-100', 'bg-green-100');
    } else {
        iconSuccess.classList.add('hidden');
        iconError.classList.remove('hidden');
        toast.classList.remove('border-green-500');
        toast.classList.add('border-red-500');
        iconContainer.classList.replace('text-green-500', 'text-red-500');
        iconContainer.classList.replace('bg-green-100', 'bg-red-100');
    }

    toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
    
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        closeToast();
    }, 6000);
}

window.closeToast = function() {
    const toast = document.getElementById('toastNotification');
    if(toast) {
        toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
    }
}
