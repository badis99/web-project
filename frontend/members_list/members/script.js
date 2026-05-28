const API_URL = "/backend/Controllers/members_list/get_members.php";

function loadUsers() {
    fetch(API_URL, { credentials: 'include' })
        .then(res => {
            if (!res.ok) {
                throw new Error(`Failed to load members: ${res.status}`);
            }
            return res.json();
        })
        .then(users => {

            const tbody = document.getElementById("table-body");
            tbody.innerHTML = "";

            users.forEach(m => {
                tbody.innerHTML += `
                    <tr>
                        <td>
                            <img src="https://luiillhngqpddvlbeeay.supabase.co/storage/v1/object/public/profile-pictures/${m.picture}" class="member-img">
                        </td>
                        <td>${m.firstname}</td>
                        <td>${m.lastname}</td>
                        <td>${m.department}</td>
                        <td>${m.fieldofstudy}</td>
                        <td>
                                <a href="/frontend/members_list/profile/index.php?id=${m.id}">ℹ️</a>
                            <button class="delete-btn" data-id="${m.id}">🧹</button>
                        </td>
                    </tr>
                `;
            });

            // DESTROY OLD INSTANCE IF EXISTS
            if ($.fn.DataTable.isDataTable("#members-list")) {
                $("#members-list").DataTable().destroy();
            }

            // INIT AFTER DATA IS READY
            $("#members-list").DataTable({
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            });
        });
}

loadUsers();


// ================================
// DELETE (YOUR OLD STYLE KEPT)
// ================================

$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    confirmDelete(id);
});

async function confirmDelete(id) {

    const result = await Swal.fire({
        title: "Are you sure you want to delete this account?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel"
    });

    if (result.isConfirmed) {

        const res = await fetch(
            `/backend/Controllers/members_list/delete_user.php?id=${id}`,
            { credentials: 'include' }
        );

        console.log("fetch status:", res.status);

        if (res.ok) {

            await Swal.fire("Deleted!", "The record has been removed.", "success");

            // instead of full reload → better UX
            loadUsers();
        }
    }
}

//filter//

document.getElementById("filter-section").addEventListener("submit", async (e) => {
    e.preventDefault();

    const col = document.querySelector("select[id='filter-column']").value;
    const val = document.querySelector("input[id='filter-value']").value;

    const res = await fetch(
        `/backend/Controllers/members_list/filter_users.php?filter-column=${col}&filter-value=${val}`,
        { credentials: 'include' }
    );

    const users = await res.json();

    if ($.fn.DataTable.isDataTable("#members-list")) {
        $("#members-list").DataTable().destroy();
    }


    const tbody = document.getElementById("table-body");
    tbody.innerHTML = "";

    users.forEach(m => {
        tbody.innerHTML += `
            <tr>
                <td><img src="https://luiillhngqpddvlbeeay.supabase.co/storage/v1/object/public/profile-pictures/${m.picture}" class="member-img"></td>
                <td>${m.firstname}</td>
                <td>${m.lastname}</td>
                <td>${m.department}</td>
                <td>${m.fieldofstudy}</td>
                <td>
                        <a href="/frontend/members_list/profile/index.php?id=${m.id}">ℹ️</a>
                    <button class="delete-btn" data-id="${m.id}">🧹</button>
                </td>
            </tr>
        `;
    });

   
    $("#members-list").DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
});