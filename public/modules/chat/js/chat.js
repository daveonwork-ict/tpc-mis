function openChatXX(chatId) {
    axios.get(`/chat/messages/${chatId}`)
        .then(res => {
            $('#chat-container').html(res.data.html || renderChatWindow(chatId, res.data));
            scrollMessages(chatId);
            listenToChat(chatId);

        });
    const newUrl = `/chat/open/${chatId}`; // Adjust according to your route
    window.history.pushState({ chatId: chatId }, '', newUrl);
}

function openChat(chatId) {
    axios.get(`/chat/messages/${chatId}`)
        .then(res => {
            $('#chat-container').html(res.data.html);
            $('.chat-item').removeClass('active');
            $('#chat-signle-item-' + chatId).addClass('active');
            listenChanel(chatId);
            scrollToBottom();
            $('.chat-container .chat-sidebar').removeClass('active');
        });

    document.getElementById(`messages-${chatId}`)?.scrollTop
    const newUrl = `/chat/open/${chatId}`; // Adjust according to your route
    window.history.pushState({ chatId: chatId }, '', newUrl);

}

function scrollToBottom() {
    const list = $('.chat-messages');
    list.scrollTop(list[0].scrollHeight);
}


function listenChanel(chatId) {
    var channel = pusher.subscribe('chat_' + chatId);
    channel.bind('MessageSent', function (data) {
        getChatList();
        openChat(chatId);
    });
}



function sendMessage(chatUuid) {
    const input = $(`#chat-input-${chatUuid}`);
    const fileInput = $(`#chat-file-${chatUuid}`)[0];
    const message = input.val().trim();

    // If no message and no file, do nothing
    if (!message && (!fileInput || !fileInput.files.length)) return;

    // Prepare FormData for text + file
    let formData = new FormData();
    formData.append('chat_uuid', chatUuid);
    if (message) formData.append('message', message);
    if (fileInput && fileInput.files.length) {
        formData.append('attachment', fileInput.files[0]);
    }

    axios.post('/chat/messages', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then(res => {
            appendMessage(chatUuid, res.data); // Your function to append message in UI
            input.val(''); // Clear input
            if (fileInput) fileInput.value = ''; // Clear file
        })
        .catch(err => console.error(err));
}

// Send message on Enter key
$(document).on('keypress', '.form-control', function (e) {
    if (e.which === 13) { // Enter key
        const chatUuid = $(this).attr('id').replace('chat-input-', '');
        sendMessage(chatUuid);
        scrollMessagesNew(chatUuid);
        e.preventDefault(); // Prevent newline
    }
});

// Optional: show selected file name
$(document).on('change', 'input[type="file"]', function () {
    const fileName = this.files[0]?.name || '';
    if (fileName) {
        $(this).closest('label').attr('title', fileName); // Hover title
    }
});

function appendMessage(chatId, data) {
    const container = $(`#messages-${chatId}`);
    if (!container.length) return;
    container.append(data.html); // Use backend-rendered HTML
}

function scrollMessagesNew(chatId) {
    const container = document.getElementById(`messages-${chatId}`);
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}



function markMessagesAsRead(chatId) {
    axios.post(`/chat/messages/${chatId}/mark-as-read`)
        .then(res => {

        })
        .catch(err => {
            console.error('Error marking messages as read:', err);
        });
}


function listenToChat(chatId) {
    Echo.join(`chat.${chatId}`)
        .listen('MessageSent', (e) => {
            appendMessage(chatId, e);
        });
}

// Helper to render chat window if response is JSON
function renderChatWindow(chatId, data) {
    let html = `<div id="chat-window-${chatId}">
        <div id="messages" style="height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;">`;
    data.forEach(msg => {
        html += `<div class="${msg.sender_id == window.chat.userId ? 'text-end' : 'text-start'}">
            <p><strong>${msg.sender.name}:</strong> ${msg.message}</p>
        </div>`;
    });
    html += `</div>
        <div class="input-group mt-2">
            <input type="text" id="chat-input-${chatId}" class="form-control" placeholder="Type a message">
            <button class="btn btn-primary" onclick="sendMessage(${chatId})">Send</button>
        </div>
    </div>`;
    return html;
}

function viewGroupMembers(chatId) {
    axios.get(`/chat/${chatId}/members`)
        .then(res => {
            const members = res.data.members;
            const isAdmin = res.data.is_admin;
            const auth_id = res.data.auth_id;

            const rows = members.map(m => `
                <tr>
                    <td>${m.name}</td>
                    <td>${m.email}</td>
                    <td>${m.role ?? (m.is_admin ? 'Admin' : 'Member')}</td>
                    <td>${m.joined_at ?? '-'}</td>
                    <td>${m.joined_by ?? '-'}</td>
                   <td>
                   <div class="d-flex gap-2">
                        ${auth_id != m.id
                    ? `<button class="btn btn-sm btn-primary" onclick="sendMessageToMember(${m.id})">
                                <i class="las la-comment"></i>
                            </button>`
                    : ''}

                        ${isAdmin && !m.is_admin
                    ? `<button class="btn btn-sm btn-danger" onclick="removeMember(${chatId}, ${m.id})">
                                <i class="las la-ban"></i>
                            </button>`
                    : ''}
                    </div>

                </td>
                </tr>
            `).join('');

            document.getElementById('group-members-list').innerHTML = rows;

            new bootstrap.Modal(document.getElementById('groupMembersModal')).show();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to fetch members!');
        });
}

function sendMessageToMember(userId) {
    axios.post('/chat/create-private', { user_id: userId })
        .then(res => {
            // res.data.id will be chat_id
            console.log('Private chat created/opened with ID:', res.data);
            openChat(res.data.uuid);
            location.reload(); // reload to update chat list
        })
        .catch(err => {
            console.error(err);
            alert('Unable to open or create conversation.');
        });
}


function closeChat(chatId) {
    document.getElementById(`chat-window-${chatId}`).remove();
}

function showSeenDetails(messageId) {
    // Open modal immediately with loading message
    const modal = new bootstrap.Modal(document.getElementById('seenDetailsModal'));
    document.getElementById('seenDetailsBody').innerHTML = '<p class="text-center text-muted">Loading...</p>';
    modal.show();

    // Fetch seen data via AJAX
    axios.get(`/chat/messages/${messageId}/seen`)
        .then(res => {
            const data = res.data;
            if (!data.length) {
                document.getElementById('seenDetailsBody').innerHTML = '<p class="text-center text-muted">No one has seen this yet.</p>';
                return;
            }

            let html = '<ul class="list-group">';
            data.forEach(item => {
                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.name}</strong>
                            <div class="small text-muted">${item.status}</div>
                        </div>
                        <span class="badge bg-${item.seen_at ? 'success' : 'secondary'} rounded-pill">
                            ${item.seen_at ? item.seen_at : 'Not seen'}
                        </span>
                    </li>
                `;
            });
            html += '</ul>';

            document.getElementById('seenDetailsBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('seenDetailsBody').innerHTML = '<p class="text-center text-danger">Error loading seen details.</p>';
        });
}


function removeMember(chatId, userId) {
    if (!confirm('Are you sure you want to remove this member?')) return;

    axios.delete(`/chat/${chatId}/members/${userId}`)
        .then(res => {
            toastr.success(res.data.message || 'Member removed successfully');
            viewGroupMembers(chatId); // ✅ refresh member list
        })
        .catch(err => {
            console.error(err);
            toastr.error(err.response?.data?.message || 'Failed to remove member');
        });
}


function editGroup(chatId) {
    axios.get(`/chat/${chatId}/edit`).then(res => {
        const chat = res.data.chat;
        $('#archive_switch').prop('checked', chat.is_archived);
        currentArchiveStatus = chat.is_archived;

        $('#edit-group-id').val(chat.id);
        $('#edit-group-name').val(chat.name);

        if (chat.icon_url) {
            $('#edit-group-preview').attr('src', chat.icon_url).show();
        } else {
            $('#edit-group-preview').hide();
        }

        // Reset member selection
        $('#edit-group-members').val([]).trigger('change');

        // Populate existing members with admin actions
        const members = chat.members.map(m => `
            <div class="d-flex justify-content-between align-items-center border p-2 mb-2">
                <div>
                    <strong>${m.name}</strong> (${m.email})
                    ${m.is_admin ? '<span class="badge bg-success">Admin</span>' : ''}
                </div>
                <div>
                    ${!m.is_admin ? `<button class="btn btn-sm btn-outline-success" onclick="makeAdmin(${chat.id}, ${m.id})">Make Admin</button>` : ''}
                    ${m.user_role_id != 1 ? `<button class="btn btn-sm btn-outline-danger" onclick="removeMember(${chat.id}, ${m.id})">Remove</button>` : ''}
                </div>
            </div>
        `).join('');


        $('#existing-members').html(members);

        new bootstrap.Modal(document.getElementById('editGroupModal')).show();
    });
}

function makeAdmin(chatId, userId) {
    if (!confirm('Are you sure you want to make this member an Admin?')) return;

    axios.post(`/chat/${chatId}/make-admin`, { user_id: userId })
        .then(res => {
            if (res.data.success) {
                alert('Member promoted to Admin successfully!');
                // Optionally reload the member list
                viewGroupMembers(chatId);
            } else {
                alert(res.data.message || 'Failed to update member role.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Something went wrong while making admin.');
        });
}


function pinMessage(messageId) {
    Swal.fire({
        title: 'Pin this message?',
        text: 'Do you want to pin this message for quick access?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, pin it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/chat/messages/${messageId}/pin`)
                .then(res => {
                    const isPinned = res.data.isPinned;

                    // Add pin icon if pinned
                    if (isPinned) {
                        Swal.fire({
                            icon: 'success',
                            title: isPinned ? 'Message pinned' : 'Message unpinned',
                            timer: 1200,
                            showConfirmButton: false
                        });
                        location.reload();
                    }


                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Unable to pin message', 'error');
                });
        }
    });
}


function unpinMessage(pinnedId) {
    Swal.fire({
        title: 'Unpin this message?',
        text: 'Are you sure you want to unpin this message?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, unpin it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/chat/messages/${pinnedId}/unpin`)
                .then(res => {
                    const is_unpinned = res.data.status;
                    if (is_unpinned) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Message unpinned',
                            timer: 1200,
                            showConfirmButton: false
                        });
                        location.reload();
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Unable to unpin message', 'error');
                });
        }
    });
}

function togglePinnedMessage(el) {
    const fullText = el.dataset.full;
    const isTruncated = el.classList.contains('truncated');

    if (isTruncated) {
        el.textContent = fullText; // show full
        el.classList.remove('truncated');
    } else {
        el.textContent = fullText.length > 100 ? fullText.substring(0, 100) + '...' : fullText;
        el.classList.add('truncated');
    }
}

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
}




function thumbsUpMessage(messageId) {
    axios.post(`/chat/messages/${messageId}/thumbs-up`)
        .then(res => {
            if (res.data.status === 'success') {
                const count = res.data.p_reactions_count;
                const users = res.data.reacted_users.map(u => u.user).join(', ');

            } else {
                // Already reacted or failed
                Swal.fire({
                    icon: 'warning',
                    title: 'Already reacted',
                    text: res.data.message || 'You have already given a thumbs up!',
                    timer: 1800,
                    showConfirmButton: false
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Unable to process your reaction', 'error');
        });
}

function showExtraReactions(messageId) {
    axios.get(`/chat/messages/${messageId}/reactions`) // You need a route returning all reacted users
        .then(res => {
            const users = res.data.reacted_users;
            let html = '';
            users.forEach(u => {
                html += `<div class="d-flex align-items-center mb-2">
                            <img src="${u.avatar}" class="rounded-circle me-2" style="width:30px; height:30px;">
                            <span>${u.user}</span>
                        </div>`;
            });

            document.getElementById('extraReactionsBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('extraReactionsModal')).show();
        })
        .catch(err => console.error(err));
}


function editMessage(messageId) {
    const messageEl = document.getElementById(`single_message_${messageId}`);
    console.log(messageId, messageEl);
    if (!messageEl) return;

    // Get current message text
    const currentText = messageEl.innerText;

    // Replace message with textarea
    const textarea = document.createElement('textarea');
    textarea.value = currentText;
    textarea.className = 'form-control form-control-sm';
    textarea.style.maxWidth = '100%';
    textarea.style.minHeight = '50px';
    textarea.style.resize = 'vertical';

    // Replace message element with textarea
    messageEl.replaceWith(textarea);
    textarea.focus();

    // Optional: place cursor at the end
    textarea.selectionStart = textarea.selectionEnd = currentText.length;

    // Handle Enter key to save edit (Shift+Enter allows new line)
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); // prevent newline
            const newMessage = textarea.value.trim();
            if (newMessage.length === 0) {
                Swal.fire('Error', 'Message cannot be empty', 'error');
                return;
            }

            axios.post(`/chat/messages/${messageId}/edit`, { message: newMessage })
                .then(res => {
                    if (res.data.status === 'success') {
                        // Replace textarea with updated message
                        const newMessageEl = document.createElement('p');
                        newMessageEl.id = `single_message_${messageId}`;
                        newMessageEl.innerText = res.data.message;
                        newMessageEl.className = 'mb-0';
                        textarea.replaceWith(newMessageEl);

                        Swal.fire({
                            icon: 'success',
                            title: 'Message updated',
                            timer: 1000,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Unable to update message', 'error');
                });
        }

        // Escape to cancel
        if (e.key === 'Escape') {
            textarea.replaceWith(messageEl);
        }
    });
}

function deleteMessage(messageId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This message will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Call backend to delete
            axios.post(`/chat/messages/delete/${messageId}`)
                .then(res => {
                    if (res.data.status === 'success') {
                        // Remove the message from DOM
                        const msgEl = document.getElementById(`mes-content-${messageId}`);
                        if (msgEl) msgEl.remove();

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Message has been deleted.',
                            timer: 1200,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Unable to delete message.', 'error');
                });
        }
    });
}

let loadingOlderMessages = false;
let currentPage = 1; // start page
const pageSize = 20; // messages per page

function initInfiniteScroll(chatId) {
    const container = document.getElementById(`messages-${chatId}`);
    console.log('initInfiniteScroll container for chat:', container);
    if (!container) return;

    container.addEventListener('scroll', async function () {
        if (loadingOlderMessages) return;

        // If scroll near top (threshold 50px)
        if (container.scrollTop <= 50) {
            loadingOlderMessages = true;
            showLoader(chatId, true);

            try {
                currentPage++; // next page (older messages)
                const res = await axios.get(`/chat/messages/${chatId}?page=${currentPage}&page_size=${pageSize}`);
                const html = res.data.html || '';

                if (html.trim()) {
                    // Save current scroll height
                    const scrollHeightBefore = container.scrollHeight;

                    // Prepend older messages
                    container.insertAdjacentHTML('afterbegin', html);

                    // Maintain scroll position
                    const scrollHeightAfter = container.scrollHeight;
                    container.scrollTop = scrollHeightAfter - scrollHeightBefore;
                } else {
                    // No more messages
                    container.removeEventListener('scroll', this);
                }
            } catch (err) {
                console.error('Error loading older messages', err);
            } finally {
                loadingOlderMessages = false;
                showLoader(chatId, false);
            }
        }
    });
}

function showLoader(chatId, show = true) {
    console.log('showLoader messages for chat:', chatId);
    const loader = document.getElementById(`messages-loader-${chatId}`);
    if (loader) loader.style.display = show ? 'block' : 'none';
}

function getChatList() {
    axios.get(`/chat/conversation-list`)
        .then(res => {
            $('#chat-list').html(res.data);
        });
}


function openViewer(src) {
    const overlay = document.createElement('div');
    overlay.className = 'image-viewer-overlay';

    const img = document.createElement('img');
    img.src = src;

    overlay.appendChild(img);

    overlay.onclick = () => overlay.remove();

    document.body.appendChild(overlay);
}

  $(document).ready(function () {
    $(document).on("click", '#chat-list-handler', function (e) {
      e.preventDefault();
      console.log('chat list handler');
      $('.chat-container .chat-sidebar').toggleClass('active');

    });
    $(document).on("click", '#chat-list-handler-close', function (e) {
      e.preventDefault();
      console.log('chat list handler close');
      $('.chat-container .chat-sidebar').removeClass('active');
    });
  });




















