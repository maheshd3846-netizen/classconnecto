// assets/js/three_nodes.js

if (document.getElementById('canvas-container')) {
    const container = document.getElementById('canvas-container');
    
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });

    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);

    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.enablePan = false;
    controls.maxDistance = 600;
    controls.minDistance = 100;

    camera.position.z = 250;

    // Subjects Data Array
    const subjects = [
        { id: 'OS', name: 'Operating Systems', type: 'theory', x: -80, y: 50, z: 0 },
        { id: 'HCI', name: 'HCI', type: 'theory', x: 80, y: 50, z: -20 },
        { id: 'HCIL', name: 'HCI Lab', type: 'lab', x: 120, y: 20, z: 20, parent: 'HCI' },
        { id: 'ADSA', name: 'ADSA', type: 'theory', x: 0, y: -40, z: 40 },
        { id: 'ADSAL', name: 'ADSA Lab', type: 'lab', x: -40, y: -70, z: 20, parent: 'ADSA' },
        { id: 'FSDL', name: 'FSD Lab', type: 'lab', x: 40, y: -80, z: 10, parent: 'ADSA' },
        { id: 'MEFA', name: 'MEFA', type: 'theory', x: -100, y: -20, z: -40 },
        { id: 'PS', name: 'P&S', type: 'theory', x: 60, y: -10, z: -60 },
        { id: 'ES', name: 'Embedded Systems', type: 'theory', x: 20, y: 80, z: -50 }
    ];

    const nodes = [];
    const sphereGeometry = new THREE.SphereGeometry(1, 32, 32);
    
    // Core Material function
    function createMaterial(color, emissive) {
         return new THREE.MeshPhongMaterial({
            color: color,
            emissive: emissive,
            shininess: 100,
            transparent: true,
            opacity: 0.9,
        });
    }

    const theoryMat = createMaterial(0x3b82f6, 0x1d4ed8);
    const labMat = createMaterial(0x8b5cf6, 0x6d28d9);

    // Sprites for Labels
    function createTextSprite(message) {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.width = 256;
        canvas.height = 128;
        context.font = "Bold 24px Arial";
        context.fillStyle = "rgba(255,255,255,1)";
        context.textAlign = "center";
        context.fillText(message, 128, 64);
        
        const texture = new THREE.CanvasTexture(canvas);
        const spriteMaterial = new THREE.SpriteMaterial({ map: texture, transparent: true });
        const sprite = new THREE.Sprite(spriteMaterial);
        sprite.scale.set(40, 20, 1);
        return sprite;
    }

    const positionsMap = {};

    // Grouping for rotation animation
    const graphGroup = new THREE.Group();
    scene.add(graphGroup);

    // Create Nodes
    subjects.forEach((sub) => {
        const mesh = new THREE.Mesh(
            new THREE.SphereGeometry(sub.type === 'theory' ? 8 : 5, 32, 32),
            sub.type === 'theory' ? theoryMat : labMat
        );
        mesh.position.set(sub.x, sub.y, sub.z);
        mesh.userData = { id: sub.id, name: sub.name, type: sub.type };
        
        // Add glowing effect (point light) to main theories
        if(sub.type === 'theory') {
            const light = new THREE.PointLight(0x3b82f6, 1, 50);
            mesh.add(light);
        }

        const label = createTextSprite(sub.name);
        label.position.set(0, sub.type === 'theory' ? 12 : 8, 0);
        mesh.add(label);

        graphGroup.add(mesh);
        nodes.push(mesh);
        positionsMap[sub.id] = mesh.position;
    });

    // Create Lines (Edges)
    const lineMaterial = new THREE.LineBasicMaterial({ color: 0x9ca3af, transparent: true, opacity: 0.4 });
    const addLine = (p1, p2) => {
        const points = [];
        points.push(p1);
        points.push(p2);
        const geometry = new THREE.BufferGeometry().setFromPoints(points);
        const line = new THREE.Line(geometry, lineMaterial);
        graphGroup.add(line);
    };

    // Connections
    subjects.forEach((sub) => {
        if (sub.parent && positionsMap[sub.parent]) {
            addLine(positionsMap[sub.id], positionsMap[sub.parent]);
        }
    });

    // Some random cross-connections to make it look like a neural graph
    addLine(positionsMap['OS'], positionsMap['ADSA']);
    addLine(positionsMap['OS'], positionsMap['ES']);
    addLine(positionsMap['HCI'], positionsMap['PS']);
    addLine(positionsMap['ADSA'], positionsMap['PS']);

    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);
    
    // Raycaster for clicks
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();

    container.addEventListener('click', onMouseClick, false);

    function onMouseClick(event) {
        event.preventDefault();
        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ( ( event.clientX - rect.left ) / rect.width ) * 2 - 1;
        mouse.y = - ( ( event.clientY - rect.top ) / rect.height ) * 2 + 1;

        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(nodes);

        if (intersects.length > 0) {
            const obj = intersects[0].object;
            // Pulse animation on click
            let s = 1.0;
            const interval = setInterval(() => {
                 s += 0.1;
                 obj.scale.set(s,s,s);
                 if(s >= 1.5) {
                     clearInterval(interval);
                     obj.scale.set(1,1,1);
                     // Navigate to subject workspace
                     window.location.href = `subject.php?id=${obj.userData.id}`;
                 }
            }, 30);
        }
    }

    // Animation Loop
    function animate() {
        requestAnimationFrame(animate);
        
        // Gentle rotation of the whole graph
        graphGroup.rotation.y += 0.001;
        graphGroup.rotation.x += 0.0005;

        // Makes text sprites face camera
        nodes.forEach(node => {
             node.children.forEach(child => {
                  if(child.type === "Sprite") {
                       child.quaternion.copy(camera.quaternion);
                  }
             });
        });

        controls.update();
        renderer.render(scene, camera);
    }
    
    animate();

    // Resize handler
    window.addEventListener('resize', () => {
        if (!container) return;
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });
}
