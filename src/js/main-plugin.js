document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-review').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.getAttribute('data-target'));
            const isExpanded = target.classList.toggle('expanded');

            if (isExpanded) {
                target.style.webkitLineClamp = 'unset';
                btn.textContent = 'Masquer';
                btn.classList.add('active');
            } else {
                target.style.webkitLineClamp = 4; // même valeur que le CSS
                btn.textContent = 'Lire la suite';
                btn.classList.remove('active');
            }
        });
    });
});


