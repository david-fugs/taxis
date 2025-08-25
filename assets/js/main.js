// JavaScript principal para el sistema de taxis

$(document).ready(function() {
    // Inicializar DataTables en todas las tablas con la clase 'data-table'
    $('.data-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "responsive": true,
        "pageLength": 25,
        "order": [[ 0, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": [-1] } // Última columna (acciones) no ordenable
        ]
    });

    // Configuración global para SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Confirmar eliminación
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const itemName = $(this).data('name') || 'este elemento';
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar ${itemName}? Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Mostrar alertas automáticamente si existen en la sesión
    if (typeof alertMessage !== 'undefined' && alertMessage) {
        showAlert(alertMessage, alertType || 'info');
    }

    // Función para mostrar alertas
    window.showAlert = function(message, type = 'info') {
        let icon = 'info';
        let title = 'Información';
        
        switch(type) {
            case 'success':
                icon = 'success';
                title = 'Éxito';
                break;
            case 'error':
                icon = 'error';
                title = 'Error';
                break;
            case 'warning':
                icon = 'warning';
                title = 'Advertencia';
                break;
        }
        
        Toast.fire({
            icon: icon,
            title: title,
            text: message
        });
    };

    // Validación de formularios
    $('.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Previsualización de imágenes
    $(document).on('change', '.image-preview-input', function() {
        const input = this;
        const preview = $(input).siblings('.image-preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.attr('src', e.target.result).show();
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Limpiar formularios en modales
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0]?.reset();
        $(this).find('.was-validated').removeClass('was-validated');
        $(this).find('.image-preview').hide();
    });

    // Auto-close alerts
    $('.alert-auto-close').delay(5000).fadeOut();

    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Cálculo automático de edad
    $(document).on('change', '.fecha-nacimiento', function() {
        const fechaNacimiento = new Date($(this).val());
        const hoy = new Date();
        let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
        const m = hoy.getMonth() - fechaNacimiento.getMonth();
        
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
            edad--;
        }
        
        $('.edad-calculada').val(edad);
    });

    // Formatear números de teléfono mientras se escribe
    $(document).on('input', '.telefono-format', function() {
        let value = $(this).val().replace(/\D/g, '');
        
        if (value.length >= 3 && value.length <= 6) {
            value = value.replace(/(\d{3})(\d+)/, '$1-$2');
        } else if (value.length >= 7) {
            value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
        }
        
        $(this).val(value);
    });

    // Validación de cédula colombiana
    $(document).on('blur', '.cedula-input', function() {
        const cedula = $(this).val().replace(/\D/g, '');
        
        if (cedula.length < 6 || cedula.length > 10) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('La cédula debe tener entre 6 y 10 dígitos');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Buscar en tiempo real
    let searchTimeout;
    $(document).on('input', '.search-realtime', function() {
        const searchTerm = $(this).val();
        const targetTable = $(this).data('target');
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            if ($.fn.DataTable.isDataTable(targetTable)) {
                $(targetTable).DataTable().search(searchTerm).draw();
            }
        }, 300);
    });

    // Máscaras de entrada
    $('.placa-mask').on('input', function() {
        let value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        if (value.length <= 3) {
            value = value.replace(/([A-Z]{0,3})/, '$1');
        } else {
            value = value.replace(/([A-Z]{3})([0-9]{0,3})/, '$1-$2');
        }
        
        $(this).val(value);
    });

    // Exportar tabla a Excel (opcional)
    $(document).on('click', '.export-excel', function() {
        const table = $(this).data('table');
        const filename = $(this).data('filename') || 'datos';
        
        if ($.fn.DataTable.isDataTable(table)) {
            // Implementar exportación a Excel si es necesario
            showAlert('Función de exportación en desarrollo', 'info');
        }
    });

    // Lazy loading para imágenes
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('.lazy').forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    // Prevenir doble envío de formularios
    $('form').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true);
        setTimeout(() => {
            $(this).find('button[type="submit"]').prop('disabled', false);
        }, 3000);
    });
});

// Función para actualizar contadores en tiempo real
function updateCounters() {
    // Implementar según necesidades específicas
}

// Función para refrescar datos sin recargar página
function refreshData(url, targetElement) {
    $.get(url)
        .done(function(data) {
            $(targetElement).html(data);
            showAlert('Datos actualizados correctamente', 'success');
        })
        .fail(function() {
            showAlert('Error al actualizar los datos', 'error');
        });
}

// Funciones de utilidad
const Utils = {
    // Formatear moneda
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP'
        }).format(amount);
    },

    // Formatear fecha
    formatDate: function(date) {
        return new Intl.DateTimeFormat('es-CO').format(new Date(date));
    },

    // Validar email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Generar ID único
    generateId: function() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
};
