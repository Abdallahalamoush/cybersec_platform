// Modern UI Interactions & Animations for Cyber Theme

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial Load Glitch Effect Sequence
    initBootSequence();

    // 2. Cursor Follower (Optional - subtle glow behind elements)
    initCursorGlow();

    // 3. Matrix Rain Effect (for specific containers like auth backgrounds)
    initMatrixRain();

    // 4. Hover Sound Effects (Optional, requires audio files - simulated here with subtle shake)
    initHoverEffects();

    // 5. Typing Effect for Terminal Texts
    initTypingEffect();
});

function initBootSequence() {
    // Add a simple boot log to the console for aesthetic
    console.log("%c SYSTEM INITIALIZATION...", "color: #00f0ff; font-weight: bold; font-family: monospace;");
    console.log("%c > Loading core modules [OK]", "color: #00ff88; font-family: monospace;");
    console.log("%c > Establishing secure connection [OK]", "color: #00ff88; font-family: monospace;");
    console.log("%c > Bypassing mainframe... [ACCESS GRANTED]", "color: #ff6b6b; font-weight: bold; font-family: monospace;");
}

function initCursorGlow() {
    // Create cursor glow element if it doesn't exist
    let glow = document.getElementById('cursor-glow');
    if (!glow) {
        glow = document.createElement('div');
        glow.id = 'cursor-glow';
        document.body.appendChild(glow);
        
        // Basic styles applied via JS to ensure it works even without specific CSS
        Object.assign(glow.style, {
            position: 'fixed',
            width: '400px',
            height: '400px',
            background: 'radial-gradient(circle, rgba(0, 240, 255, 0.05) 0%, rgba(0, 0, 0, 0) 70%)',
            borderRadius: '50%',
            pointerEvents: 'none',
            top: '0',
            left: '0',
            transform: 'translate3d(-50%, -50%, 0)', // initial state
            zIndex: '0',
            transition: 'opacity 0.3s ease', // removed top/left transitions
            opacity: '0',
            willChange: 'transform' // hint for GPU
        });
    }

    let mouseX = 0, mouseY = 0;
    let isMoving = false;

    // Move glow using requestAnimationFrame for performance optimization
    window.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        glow.style.opacity = '1';
        
        if (!isMoving) {
            isMoving = true;
            requestAnimationFrame(updateCursor);
        }
    });

    function updateCursor() {
        glow.style.transform = `translate3d(${mouseX - 200}px, ${mouseY - 200}px, 0)`;
        isMoving = false;
    }

    // Hide glow when mouse leaves window
    window.addEventListener('mouseout', () => {
        glow.style.opacity = '0';
    });
}

function initMatrixRain() {
    const canvas = document.getElementById('matrix-rain');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // Resize canvas to a lower resolution and scale up to fit screen for performance
    const dpr = 0.5; // Render at 50% resolution to save processing power
    const resizeCanvas = () => {
        canvas.width = window.innerWidth * dpr;
        canvas.height = window.innerHeight * dpr;
        ctx.scale(dpr, dpr);
    };
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()_+-=[]{}|;:,.<>?/アイウエオカキクケコサシスセソタチツテトナニヌネノ';
    const drops = [];
    const fontSize = 16;
    const columns = (window.innerWidth) / fontSize;

    for (let x = 0; x < columns; x++) {
        drops[x] = 1;
    }

    // Use requestAnimationFrame with a throttle for smoother performance
    let lastDrawTime = 0;
    const throttle = 50; // Minimum ms between frames (approx 20fps)

    function draw(timestamp) {
        requestAnimationFrame(draw);
        if (timestamp - lastDrawTime < throttle) return;
        lastDrawTime = timestamp;

        ctx.fillStyle = 'rgba(3, 5, 8, 0.1)'; // slightly faster fade
        ctx.fillRect(0, 0, (window.innerWidth), (window.innerHeight));

        ctx.fillStyle = '#00f0ff';
        ctx.font = `${fontSize}px monospace`;

        for (let i = 0; i < drops.length; i++) {
            const text = chars.charAt(Math.floor(Math.random() * chars.length));
            ctx.fillText(text, i * fontSize, drops[i] * fontSize);

            if (drops[i] * fontSize > window.innerHeight && Math.random() > 0.95) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }

    requestAnimationFrame(draw);
}

function initHoverEffects() {
    // Add subtle structural pulse to all primary buttons and links
    const interactables = document.querySelectorAll('.btn, a.btn, .card a:not(.btn)');
    
    interactables.forEach(el => {
        el.addEventListener('mouseenter', () => {
            // Add a temporary 'glitch' data attribute or class if desired
            el.classList.add('hover-active');
            
            // Add sound here if audio files are available:
            // new Audio('/assets/sounds/hover.mp3').play().catch(()=>console.log('Audio disabled'));
        });
        
        el.addEventListener('mouseleave', () => {
            el.classList.remove('hover-active');
        });
    });
}

function initTypingEffect() {
    // Find all elements with the 'typewriter' class
    const typingElements = document.querySelectorAll('.typewriter');
    
    typingElements.forEach(el => {
        const text = el.innerHTML;
        el.innerHTML = ''; // Clear existing text
        el.style.visibility = 'visible'; // Ensure it's visible now
        
        let i = 0;
        const speed = parseInt(el.getAttribute('data-speed')) || 30; // ms per character
        
        function typeWriter() {
            if (i < text.length) {
                // If it's HTML tag, skip to end of tag to avoid breaking it
                if (text.charAt(i) === '<') {
                    let tagEnd = text.indexOf('>', i);
                    if (tagEnd !== -1) {
                        i = tagEnd + 1;
                    }
                }
                
                el.innerHTML = text.substring(0, i + 1) + '<span class="cursor" style="opacity:1;">_</span>';
                i++;
                setTimeout(typeWriter, speed + (Math.random() * 20)); // slight randomness makes it more realistic
            } else {
                // Finished
                el.innerHTML = text; // finalize without cursor (or leave cursor blinking based on preference)
            }
        }
        
        // Start typing after a short delay
        setTimeout(typeWriter, 500);
    });
}
