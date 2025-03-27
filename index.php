<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notebook Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col items-center min-h-screen p-5">
<input type="hidden" id="notebook-id">
    <!-- Main Notebook List Container -->
<div id="notebook-list-container" class="w-full max-w-7xl h-[99vh] bg-white shadow-lg rounded-lg flex flex-col overflow-hidden">

        <!-- Title Bar with (+) Button -->
        <div class="bg-gray-200 flex justify-between items-center py-3 px-4 text-xl font-semibold">
            <span>Notebook List</span>
            <button onclick="createNewNotebook()" class="bg-green-500 text-white px-3 py-1 rounded-full shadow-md text-lg">
                +
            </button>
        </div>

        <!-- List of Notebooks -->
        <div id="notebook-list" class="p-4 space-y-2 overflow-y-auto flex-1"></div>

        <!-- Search & Pagination -->
        <div class="bg-gray-200 p-3 flex justify-between items-center">
            <button onclick="prevPage()" id="prevBtn" class="bg-gray-400 text-white px-3 py-1 rounded-md shadow-sm">⬅️ Prev</button>
            
            <input type="text" id="searchBar" placeholder="Search Notebooks..." class="p-2 border rounded-md w-1/3" oninput="searchNotebooks()">
            
            <button onclick="nextPage()" id="nextBtn" class="bg-gray-400 text-white px-3 py-1 rounded-md shadow-sm">Next ➡️</button>
        </div>
    </div>

    <!-- Notebook Editor Container -->
    <div id="notebook-editor-container" class="w-full max-w-7.5xl h-[90vh] bg-white shadow-lg rounded-lg flex flex-col overflow-hidden mt-5 hidden">

        <!-- Editor Title Bar -->
        <div class="bg-gray-200 flex justify-between items-center py-3 px-4 text-xl font-semibold">
            <input id="notebook-title" class="bg-transparent text-xl font-semibold outline-none border-b px-2 w-3/4" placeholder="Notebook Title">
            <button onclick="saveNotebook()" class="bg-blue-500 text-white px-3 py-1 rounded-md shadow-md">Save</button>
        </div>

        <!-- Content Section -->
        <div class="flex flex-1">

            <!-- Left Side: Notes Section -->
            <div class="w-1/3 bg-gray-50 border-r p-4 overflow-y-auto">
                <button onclick="addNote()" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md shadow-md mb-4">
                    + Add Note
                </button>
                <div id="notes-container" class="space-y-3"></div>
            </div>

            <!-- Right Side: PDF Viewer -->
            <div class="w-2/3 p-4 flex flex-col">
                <input type="file" id="pdfInput" accept="application/pdf" class="mb-3">
                <iframe id="pdfViewer" class="w-full flex-1 border rounded-md"></iframe>
            </div>

        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let searchQuery = "";
        fetchNotebooks();
        function fetchNotebooks(page = 1, search = "") {
            fetch(`get_notebooks.php?page=${page}&search=${search}`)
                .then(response => response.json())
                .then(data => {
                    let container = document.getElementById("notebook-list");
                    container.innerHTML = "";

                    data.notebooks.forEach(notebook => {
                        let div = document.createElement("div");
                        div.classList.add("flex", "justify-between", "items-center", "bg-gray-100", "p-3", "rounded-md", "shadow-sm");

                        let title = document.createElement("span");
                        title.textContent = notebook.title;
                        div.appendChild(title);

                        let editBtn = document.createElement("button");
                        editBtn.textContent = "✏️";
                        editBtn.classList.add("bg-gray-300", "px-2", "py-1", "rounded-md", "shadow-sm");
                        editBtn.onclick = () => loadNotebook(notebook.id);
                        div.appendChild(editBtn);

                        container.appendChild(div);
                    });

                    totalPages = data.totalPages;
                    currentPage = data.currentPage;
                    document.getElementById("prevBtn").disabled = (currentPage === 1);
                    document.getElementById("nextBtn").disabled = (currentPage === totalPages);
                })
                .catch(error => console.error("Error fetching notebooks:", error));
        }

        function prevPage() {
            if (currentPage > 1) {
                fetchNotebooks(--currentPage, searchQuery);
            }
        }

        function nextPage() {
            if (currentPage < totalPages) {
                fetchNotebooks(++currentPage, searchQuery);
            }
        }

        function searchNotebooks() {
            searchQuery = document.getElementById("searchBar").value;
            fetchNotebooks(1, searchQuery);
        }

        function createNewNotebook() {
            document.getElementById("notebook-list-container").classList.add("hidden");
            document.getElementById("notebook-editor-container").classList.remove("hidden");
            document.getElementById("notebook-title").value = "";
            document.getElementById("notes-container").innerHTML = "";
        }
        function loadNotebook(id) {
    fetch(`get_notebook.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("Error loading notebook:", data.error);
                return;
            }

            document.getElementById("notebook-id").value = data.id;
            document.getElementById("notebook-title").value = data.title;

            let notesContainer = document.getElementById("notes-container");
            notesContainer.innerHTML = "";
            data.notes.forEach(note => {
                let noteDiv = document.createElement("div");
                let noteArea = document.createElement("textarea");
                noteArea.classList.add(
                    "w-full", "h-24", "p-2", "border", "rounded-md", 
                    "resize-none", "focus:outline-none", "focus:ring-2", "focus:ring-blue-500","note"
                );
                noteArea.value = note;
                noteDiv.classList.add("note", "bg-gray-200", "p-2", "rounded-md", "shadow-sm");
                noteDiv.appendChild(noteArea);
                noteArea.setAttribute("readonly","true");
                noteDiv.ondblclick = function () {
                    noteArea.removeAttribute("readonly"); // Enable editing
                    noteArea.focus();
                };
                notesContainer.appendChild(noteDiv);
            });

            // Set the PDF file in viewer if it exists
            if (data.pdf_path) {
                document.getElementById("pdfViewer").src = data.pdf_path;
            } else {
                document.getElementById("pdfViewer").src = "";
            }

            // Show the editor, hide the list
            document.getElementById("notebook-list-container").classList.add("hidden");
            document.getElementById("notebook-editor-container").classList.remove("hidden");
        })
        .catch(error => console.error("Error loading notebook:", error));
}


function saveNotebook() {
    let id = document.getElementById("notebook-id").value.trim();
    let title = document.getElementById("notebook-title").value.trim();
    
    if (!title) {
        alert("Notebook title cannot be empty!");
        return;
    }

    // **Get all existing notes (including displayed and new ones)**
    let notes = [];
    document.querySelectorAll("#notes-container .note").forEach(noteElement => {
        let text = noteElement.tagName === "TEXTAREA" ? noteElement.value : noteElement.textContent.trim();
        if (text) {
            notes.push(text);
        }
    });

    // **Prepare FormData for PHP (Supports File Upload)**
    let formData = new FormData();
    formData.append("id", id);
    formData.append("title", title);
    formData.append("notes", JSON.stringify(notes));
    let pdfInput = document.getElementById("pdfInput");
    if (pdfInput.files.length > 0) {
        formData.append("pdf", pdfInput.files[0]);  // Attach new PDF file
    }

    // **Send Data to PHP**
    fetch("save_notebook.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Notebook saved successfully!");
            fetchNotebooks();  // Refresh notebook list
            loadNotebook(id);  // Reload this notebook
        } else {
            console.error("Save error:", data.error);
            alert("Error saving notebook!");
        }
    })
    .catch(error => console.error("Error:", error));
}


function addNote() {
    let notesContainer = document.getElementById("notes-container");

    if (!notesContainer) {
        console.error("Error: Notes container not found.");
        return;
    }

    // Create note card container
    let noteCard = document.createElement("div");
    noteCard.classList.add(
        "bg-white", "shadow-md", "rounded-lg", "p-4", 
        "flex", "flex-col", "gap-2", "border", "relative"
    );

    // Create textarea for note content
    let noteInput = document.createElement("textarea");
    noteInput.classList.add(
        "w-full", "h-24", "p-2", "border", "rounded-md", 
        "resize-none", "focus:outline-none", "focus:ring-2", "focus:ring-blue-500","note"
    );
    noteInput.placeholder = "Enter your note here...";

    // Create delete button
    let deleteBtn = document.createElement("button");
    deleteBtn.textContent = "🗑️";
    deleteBtn.classList.add(
        "absolute", "top-2", "right-2", "text-red-500", 
        "hover:text-red-700", "p-1", "rounded-full"
    );
    deleteBtn.onclick = function () {
        notesContainer.removeChild(noteCard);
    };

    // Append elements to the note card
    noteCard.appendChild(noteInput);
    noteCard.appendChild(deleteBtn);
    notesContainer.appendChild(noteCard);
}



    </script>
</body>
</html>
