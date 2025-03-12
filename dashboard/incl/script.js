if(typeof localStorage.navbar_state == "undefined") localStorage.navbar_state = 'true';

const dashboardLoader = document.getElementById("dashboard-loader");
const dashboardBody = document.getElementById("dashboard-body");

if(localStorage.navbar_state == 'true') dashboardBody.classList.add("hide");
else dashboardBody.classList.remove("hide");

window.addEventListener('load', () => {
	setTimeout(() => dashboardLoader.classList.add("hide"), 200);
});

const navbarButtons = document.querySelectorAll("nav button[href]");
navbarButtons.forEach(navbarButton => {
	navbarButton.addEventListener("mouseup", (event) => {
		const href = event.target.getAttribute("href");
		
		switch(event.button) {
			case 0:
				goToPage(href);
				break;
			case 1:
				const openNewTab = document.createElement("a");
				openNewTab.href = href;
				openNewTab.target = "_blank";
				openNewTab.click();
				break;
		}
	})
})

function toggleNavbar() {
	localStorage.navbar_state = localStorage.navbar_state == 'false' ? 'true' : 'false';
	
	if(localStorage.navbar_state == 'true') dashboardBody.classList.add("hide");
	else dashboardBody.classList.remove("hide");
}

async function goToPage(href) {
	dashboardLoader.classList.remove("hide");
	
	const pageRequest = await fetch(href);
	const response = await pageRequest.text();
	
	const newPageBody = new DOMParser().parseFromString(response, "text/html").getElementById("dashboard-page");
	
	document.getElementById("dashboard-page").replaceWith(newPageBody);
	
	dashboardLoader.classList.add("hide");
}