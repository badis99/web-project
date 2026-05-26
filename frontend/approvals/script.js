document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(".btn").forEach(button => {

        button.addEventListener("click", async (e) => {

            const card = e.target.closest(".card");
            const id = card?.dataset?.id;

            if (!id) {
                console.error("❌ ID introuvable");
                return;
            }

            const action = e.target.classList.contains("accept")
                ? "accept"
                : "decline";

            const result = await Swal.fire({
                title: "Are you sure?",
                text: action === "accept"
                    ? "This user will be accepted"
                    : "This request will be declined",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                background: "#0a0a0a",
                color: "#fff",
                confirmButtonColor: "#c0c0c0",
                cancelButtonColor: "#444"
            });

            if (!result.isConfirmed) return;

            console.log("➡️ Envoi:", { id, action });
            console.log("ID envoyé:", id);
            console.log("Type:", typeof id);

            try {
                const res = await fetch("../../backend/routes/api.php?action=approval", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ id, action })
                });

                const text = await res.text();
                console.log("📨 Réponse brute:", text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    console.error("❌ Réponse non JSON !");
                    return;
                }

                console.log("✅ JSON:", data);

                if (data.success) {

                    card.style.transition = "0.3s";
                    card.style.opacity = "0";
                    card.style.transform = "scale(0.8)";

                    setTimeout(() => {
                        card.remove();
                        const remaining = document.querySelectorAll(".card:not(.empty-card)");
                        const badge = document.querySelector("#pending-badge");
                        if (badge) {
                            const count = remaining.length;
                            badge.textContent = count + " request" + (count !== 1 ? "s" : "") + " pending";
                            }
                        // Si plus aucune carte réelle → afficher la carte vide
                        const container = document.querySelector(".card-container");
                        const remainingInContainer = container.querySelectorAll(".card:not(.empty-card)");

                        if (remainingInContainer.length === 0 && !container.querySelector(".empty-card")) {
                            const emptyCard = document.createElement("div");
                            emptyCard.className = "card empty-card";
                            emptyCard.style.opacity = "0";
                            emptyCard.style.transition = "opacity 0.4s ease";
                            emptyCard.innerHTML = `
                                <div class="card-content" style="text-align:center; padding: 20px 0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                                        stroke="rgba(192,192,192,0.4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                        style="display:block; margin: 0 auto 20px;">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="9" y1="13" x2="15" y2="13"/>
                                    </svg>
                                    <span class="label" style="display:block; margin-bottom: 10px; font-size: 11px; letter-spacing: 2px;">Aucune demande</span>
                                    <span class="value" style="font-size: 17px; color: rgba(200,200,200,0.6);">Pas de demande d'ajout</span>
                                </div>
                            `;
                            container.appendChild(emptyCard);

                            // Fade in
                            requestAnimationFrame(() => {
                                emptyCard.style.opacity = "1";
                            });
                        }
                    }, 300);

                    Swal.fire({
                        title: "Done",
                        text: "Action completed",
                        icon: "success",
                        timer: 1200,
                        showConfirmButton: false,
                        background: "#0a0a0a",
                        color: "#fff"
                    });

                } else {
                    Swal.fire({
                        title: "Error",
                        text: data.message || data.error || "Erreur inconnue",
                        icon: "error",
                        background: "#0a0a0a",
                        color: "#fff"
                    });
                }

            } catch (err) {
                console.error("❌ Erreur fetch:", err);

                Swal.fire({
                    title: "Error",
                    text: "Server error",
                    icon: "error",
                    background: "#0a0a0a",
                    color: "#fff"
                });
            }

        });

    });

});