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

    // Disable on mobile entirely
    if (window.innerWidth < 768) {
        canvas.style.display = 'none';
        return;
    }

    const ctx = canvas.getContext('2d');

    // Render at 25% resolution and scale up — massive perf gain
    const dpr = 0.25;
    canvas.width  = window.innerWidth  * dpr;
    canvas.height = window.innerHeight * dpr;
    ctx.setTransform(1, 0, 0, 1, 0, 0); // no ctx.scale needed

    // Fewer, simpler characters
    const chars = '01ABCDEF@#$%';
    const fontSize = 10;
    const columns  = Math.floor(canvas.width / fontSize);
    const drops    = Array(columns).fill(1);

    let lastDrawTime = 0;
    let animId;

    function draw(timestamp) {
        animId = requestAnimationFrame(draw);

        // Drop to ~8fps for the rain — imperceptible difference, huge savings
        if (timestamp - lastDrawTime < 120) return;
        lastDrawTime = timestamp;

        ctx.fillStyle = 'rgba(3, 5, 8, 0.15)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#00f0ff';
        ctx.font = `${fontSize}px monospace`;

        for (let i = 0; i < drops.length; i++) {
            const text = chars[Math.floor(Math.random() * chars.length)];
            ctx.fillText(text, i * fontSize, drops[i] * fontSize);
            if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }

    // Pause when tab is hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cancelAnimationFrame(animId);
        } else {
            lastDrawTime = 0;
            animId = requestAnimationFrame(draw);
        }
    });

    animId = requestAnimationFrame(draw);
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
