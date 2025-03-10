// floating-posts.js - Modified to keep notes hidden until user interaction
document.addEventListener('DOMContentLoaded', function() {
  // container utama 
  const floatingContainer = document.createElement('div');
  floatingContainer.id = 'floating-posts-overlay';
  document.body.appendChild(floatingContainer);
  
  // CSS untuk floating notes
  const styleElement = document.createElement('style');
  styleElement.textContent = `
    #floating-posts-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 9999;
      pointer-events: none;
    }
    
    #notes-container {
      display: none; /* Hidden by default */
    }
    
    .floating-note {
      position: absolute;
      padding: 12px;
      border-radius: 8px;
      max-width: 250px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transform: translate(-50%, -50%);
      cursor: pointer;
      transition: none;
      pointer-events: auto;
      z-index: 10000;
    }
    
    .floating-note:hover {
      transform: translate(-50%, -50%) scale(1.1);
    }
    
    .note-text {
      margin: 0;
      font-weight: 500;
      color: #333;
    }
    
    .note-date {
      margin-top: 8px;
      font-size: 12px;
      color: #666;
      font-style: italic;
    }
    
    .control-panel {
      position: fixed;
      bottom: 16px;
      right: 16px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      z-index: 10000;
      pointer-events: auto;
    }
    
    .control-btn {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      border: none;
      color: white;
      font-size: 24px;
    }
    
    .add-btn {
      background-color: #3b82f6;
    }
    
    .refresh-btn {
      background-color: #22c55e;
    }
    
    .note-form {
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10001;
      pointer-events: auto;
    }
    
    .form-container {
      background-color: white;
      border-radius: 8px;
      padding: 24px;
      width: 100%;
      max-width: 500px;
    }
    
    .note-form textarea {
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 12px;
      min-height: 120px;
      margin-bottom: 16px;
    }
    
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    
    .detail-view {
      position: fixed;
      inset: 0;
      background-color: rgba(255, 255, 255, 0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10001;
      pointer-events: auto;
    }
    
    .detail-container {
      background-color: white;
      border-radius: 8px;
      padding: 24px;
      width: 100%;
      max-width: 600px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .detail-date {
      margin-top: 8px;
      font-size: 14px;
      color: #666;
      font-style: italic;
    }
    
    .hidden {
      display: none !important;
    }
    
    .pause-overlay {
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.3);
      z-index: 9998;
      pointer-events: auto;
    }
    
    .back-button {
      position: fixed;
      top: 16px;
      left: 16px;
      background-color: #3b82f6;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      z-index: 10002;
      cursor: pointer;
      pointer-events: auto;
    }
  `;
  document.head.appendChild(styleElement);
  
  // Struktur HTML
  floatingContainer.innerHTML = `
    <div id="notes-container"></div>
    
    <div class="control-panel">
      <button class="control-btn add-btn" id="add-note-btn">+</button>
      <button class="control-btn refresh-btn" id="refresh-btn">↻</button>
    </div>
    
    <div class="pause-overlay hidden" id="pause-overlay"></div>
    <button class="back-button hidden" id="back-button">Back</button>
    
    <div class="note-form hidden" id="note-form">
      <div class="form-container">
        <h2>Add Anonymous Note</h2>
        <textarea id="note-text" placeholder="Write your anonymous note here..."></textarea>
        <div class="form-actions">
          <button id="cancel-btn">Cancel</button>
          <button id="submit-btn" style="background-color: #3b82f6; color: white;">Post</button>
        </div>
      </div>
    </div>
    
    <div class="detail-view hidden" id="detail-view">
      <div class="detail-container">
        <h2>Anonymous Note</h2>
        <p id="detail-text">Note content will appear here</p>
        <p id="detail-date" class="detail-date"></p>
        <button id="detail-back-btn" style="background-color: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; border: none; margin-top: 16px;">
          Back to Homepage
        </button>
      </div>
    </div>
  `;
  
  // Referensi elemen
  const notesContainer = document.getElementById('notes-container');
  const addNoteBtn = document.getElementById('add-note-btn');
  const refreshBtn = document.getElementById('refresh-btn');
  const noteForm = document.getElementById('note-form');
  const noteText = document.getElementById('note-text');
  const submitBtn = document.getElementById('submit-btn');
  const cancelBtn = document.getElementById('cancel-btn');
  const detailView = document.getElementById('detail-view');
  const detailText = document.getElementById('detail-text');
  const detailDate = document.getElementById('detail-date');
  const detailBackBtn = document.getElementById('detail-back-btn');
  const pauseOverlay = document.getElementById('pause-overlay');
  const backButton = document.getElementById('back-button');
  
  // State
  let notes = [];
  let isPaused = false;
  let storedNotes = [];
  let isVisible = false; // Default to hidden
  let animationRunning = false;
  
  // Format tanggal
  function formatDate(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
  
  
  try {
    const savedNotes = localStorage.getItem('anonymous-notes');
    if (savedNotes) {
      storedNotes = JSON.parse(savedNotes);
      // Only store the data, don't create elements or display anything
    }
  } catch (e) {
    console.error('Failed to load saved notes', e);
  }
  
  // Event listeners
  addNoteBtn.addEventListener('click', () => {
    // Show note form when + button is clicked
    noteForm.classList.remove('hidden');
  });
  
  refreshBtn.addEventListener('click', () => {
    // When refresh button is clicked, load and show notes
    loadNotesFromStorage();
    showNotes();
  });
  
  cancelBtn.addEventListener('click', () => {
    noteForm.classList.add('hidden');
    noteText.value = '';
  });
  
  submitBtn.addEventListener('click', addNote);
  
  detailBackBtn.addEventListener('click', () => {
    detailView.classList.add('hidden');
  });
  
  // Back button - menghilangkan floating notes
  backButton.addEventListener('click', hideNotes);
  
  // Fungsi untuk pause notes saat note di klik
  document.addEventListener('click', (e) => {
    // Hanya trigger pause jika klik pada floating note dan notes sedang visible
    if (isVisible && e.target.closest('.floating-note')) {
      e.stopPropagation();
      
      const noteElement = e.target.closest('.floating-note');
      const noteId = noteElement.id.replace('note-', '');
      const note = notes.find(n => n.id == noteId);
      
      if (!isPaused) {
        // Pause notes dan tampilkan tombol back dengan overlay gelap
        isPaused = true;
        pauseOverlay.classList.remove('hidden');
        backButton.classList.remove('hidden');
      } else {
        // Jika sudah paused, tampilkan detail
        showNoteDetail(note);
      }
    }
  });
  
  // Function to load notes from storage into memory
  function loadNotesFromStorage() {
    // Clear existing notes first
    notes = [];
    notesContainer.innerHTML = '';
    
    if (storedNotes.length === 0) {
      // No stored notes, create example note
      const timestamp = new Date().toISOString();
      const exampleNote = {
        id: Date.now(),
        text: "Click to pause notes, click note to view details. Click 'Back' to hide notes. Click '+' to add new notes.",
        position: {
          x: Math.random() * 80,
          y: Math.random() * 80,
        },
        speed: {
          x: (Math.random() - 0.5) * 0.2,
          y: (Math.random() - 0.5) * 0.2,
        },
        color: `hsl(${Math.random() * 360}, 70%, 80%)`,
        timestamp: timestamp
      };
      
      notes.push(exampleNote);
      storedNotes.push({
        id: exampleNote.id,
        text: exampleNote.text,
        color: exampleNote.color,
        timestamp: timestamp
      });
      
      // Save to localStorage
      try {
        localStorage.setItem('anonymous-notes', JSON.stringify(storedNotes));
      } catch (e) {
        console.error('Failed to save notes', e);
      }
      
      createNoteElement(exampleNote);
    } else {
      // Load saved notes into memory
      storedNotes.forEach(note => {
        const newNote = {
          ...note,
          position: {
            x: Math.random() * 80,
            y: Math.random() * 80,
          },
          speed: {
            x: (Math.random() - 0.5) * 0.2,
            y: (Math.random() - 0.5) * 0.2,
          }
        };
        notes.push(newNote);
        createNoteElement(newNote);
      });
    }
  }
  
  // Fungsi untuk menampilkan notes
  function showNotes() {
    // Always reload notes from storage to ensure we have the latest
    loadNotesFromStorage();
    
    // Make them visible
    notesContainer.style.display = 'block';
    isVisible = true;
    isPaused = false;
    backButton.classList.add('hidden');
    pauseOverlay.classList.add('hidden');
    
    // Start animation
    if (!animationRunning) {
      animationRunning = true;
      requestAnimationFrame(updateNotesPosition);
    }
    
    console.log('Notes are now visible');
  }
  
  // Fungsi untuk menyembunyikan notes
  function hideNotes() {
    notesContainer.style.display = 'none';
    isVisible = false;
    backButton.classList.add('hidden');
    pauseOverlay.classList.add('hidden');
  }
  
  // Fungsi tambah note
  function addNote() {
    const text = noteText.value.trim();
    if (text === '') return;
    
    const timestamp = new Date().toISOString();
    
    const note = {
      id: Date.now(),
      text: text,
      position: {
        x: Math.random() * 80,
        y: Math.random() * 80,
      },
      speed: {
        x: (Math.random() - 0.5) * 0.2,
        y: (Math.random() - 0.5) * 0.2,
      },
      color: `hsl(${Math.random() * 360}, 70%, 80%)`,
      timestamp: timestamp
    };
    
    // Add to notes array
    notes.push(note);
    
    // Add to stored notes
    storedNotes.push({
      id: note.id,
      text: note.text,
      color: note.color,
      timestamp: timestamp
    });
    
    // Save to localStorage
    try {
      localStorage.setItem('anonymous-notes', JSON.stringify(storedNotes));
    } catch (e) {
      console.error('Failed to save notes', e);
    }
    
    // Create DOM element
    createNoteElement(note);
    
    // Show notes after adding a new one
    showNotes();
    
    // Hide form
    noteForm.classList.add('hidden');
    noteText.value = '';
  }
  
  function createNoteElement(note) {
    const noteElement = document.createElement('div');
    noteElement.className = 'floating-note';
    noteElement.id = `note-${note.id}`;
    noteElement.style.left = `${note.position.x}%`;
    noteElement.style.top = `${note.position.y}%`;
    noteElement.style.backgroundColor = note.color;
    
    // Tambahkan teks dan tanggal
    noteElement.innerHTML = `
      <p class="note-text">${note.text}</p>
      <p class="note-date">${formatDate(note.timestamp)}</p>
    `;
    
    notesContainer.appendChild(noteElement);
  }
  
  function showNoteDetail(note) {
    detailText.textContent = note.text;
    detailDate.textContent = `Posted on: ${formatDate(note.timestamp)}`;
    detailView.classList.remove('hidden');
  }
  
  // Fungsi animasi
  function updateNotesPosition() {
    if (isPaused || !isVisible) {
      animationRunning = false;
      return;
    }
    
    animationRunning = true;
    
    for (let i = 0; i < notes.length; i++) {
      const note = notes[i];
      
      // Update posisi
      note.position.x += note.speed.x;
      note.position.y += note.speed.y;
      
      // Bouncing pada tepi layar
      if (note.position.x <= 0 || note.position.x >= 90) {
        note.speed.x = -note.speed.x;
      }
      
      if (note.position.y <= 0 || note.position.y >= 90) {
        note.speed.y = -note.speed.y;
      }
      
      // Batasi posisi
      note.position.x = Math.max(0, Math.min(90, note.position.x));
      note.position.y = Math.max(0, Math.min(90, note.position.y));
      
      // Update DOM
      const noteElement = document.getElementById(`note-${note.id}`);
      if (noteElement) {
        noteElement.style.left = `${note.position.x}%`;
        noteElement.style.top = `${note.position.y}%`;
      }
    }
    
    // Request next frame
    requestAnimationFrame(updateNotesPosition);
  }
  
  // Ensure visibility is explicitly set to hidden at initialization
  notesContainer.style.display = 'none';
  isVisible = false;
  
  console.log('Floating notes initialized but hidden until user interaction');
});