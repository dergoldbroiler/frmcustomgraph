const handleTooltip = () => {
    let bricks = document.querySelectorAll('.customgraph-brick');
    bricks.forEach(brick => {
        brick.addEventListener('mouseenter', function() {
            let tooltip = brick.querySelector('.customgraph-tooltip');
            tooltip.style.opacity = 1;
        });
        brick.addEventListener('mouseleave', function() {
            let tooltip = brick.querySelector('.customgraph-tooltip');
            tooltip.style.opacity = 0;
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    handleTooltip();
});