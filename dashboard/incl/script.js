if(typeof localStorage.navbar_state == "undefined") localStorage.navbar_state = 'true';
if(typeof localStorage.player_volume == "undefined") localStorage.player_volume = 0.2;

var dashboardLoader, dashboardBody, dashboardBase;

window.addEventListener('load', () => {
	dashboardLoader = document.getElementById("dashboard-loader");
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	
	if(localStorage.navbar_state == 'true') dashboardBody.classList.add("hide");
	else dashboardBody.classList.remove("hide");
	
	registerNavbarButtons();
	
	window.addEventListener("popstate", (e) => getPage(e.target.location.pathname, true));
	
	setTimeout(() => dashboardLoader.classList.add("hide"), 200);
});

function toggleNavbar() {
	localStorage.navbar_state = localStorage.navbar_state == 'false' ? 'true' : 'false';
	
	if(localStorage.navbar_state == 'true') dashboardBody.classList.add("hide");
	else dashboardBody.classList.remove("hide");
}

async function getPage(href, skipCheck = false) {
	if(!skipCheck && ((window.location.href.endsWith(href) && href.length) || (!href.length && dashboardBase.getAttribute("href") == './'))) return false;
	
	dashboardLoader.classList.remove("hide");
	
	const pageRequest = await fetch(href);
	const response = await pageRequest.text();
	
	await changePage(response, href, skipCheck);
	
	dashboardLoader.classList.add("hide");
	
	return true;
}

async function postPage(href, form) {
	const formElement = document.querySelector("form[name=" + form + "]");
	const formData = new FormData(formElement);
	const formEntries = formData.entries();
	
	for(const entry of formEntries) {
		const isOptional = formElement.querySelector("input[dashboard-not-required][name=" + entry[0] + "]");
		if(!entry[1].trim().length && isOptional == null) {
			formElement.classList.add("empty-fields");
			return false;
		}
	}

	dashboardLoader.classList.remove("hide");
	
	const pageRequest = await fetch(href, {
		method: "POST",
		body: formData
	});
	const response = await pageRequest.text();
	
	href = pageRequest.url;
	
	await changePage(response, href);
	
	dashboardLoader.classList.add("hide");
}

function changePage(response, href, skipCheck = false) {
	newPageBody = new DOMParser().parseFromString(response, "text/html");
	
	if(!skipCheck) history.pushState(null, null, href);

	document.getElementById("dashboard-page").replaceWith(newPageBody.getElementById("dashboard-page"));
	document.querySelector("base").replaceWith(newPageBody.querySelector("base"));
	document.querySelector("title").replaceWith(newPageBody.querySelector("title"));
	document.querySelector("nav").replaceWith(newPageBody.querySelector("nav"));
	
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	
	registerNavbarButtons();
}

function registerNavbarButtons() {
	const navbarButtons = document.querySelectorAll("nav button[href]");
	navbarButtons.forEach(navbarButton => {
		navbarButton.addEventListener("mouseup", (event) => {
			const href = event.target.getAttribute("href");
			switch(event.button) {
				case 0:
					getPage(href);
					break;
				case 1:
					const openNewTab = document.createElement("a");
					openNewTab.href = href;
					openNewTab.target = "_blank";
					openNewTab.click();
					break;
			}
		});
	});
}

async function postToast(href, form) {
	const formElement = document.querySelector("form[name=" + form + "]");
	const formData = new FormData(formElement);
	const formEntries = formData.entries();
	
	for(const entry of formEntries) {
		const isOptional = formElement.querySelector("input[dashboard-not-required][name=" + entry[0] + "]");
		if(!entry[1].trim().length && isOptional == null) {
			formElement.classList.add("empty-fields");
			return false;
		}
	}

	dashboardLoader.classList.remove("hide");
	
	const pageRequest = await fetch(href, {
		method: "POST",
		body: formData
	});
	const response = await pageRequest.text();
	
	const toastBody = new DOMParser().parseFromString(response, "text/html").getElementById("toast");
	
	if(toastBody == 'null') return changePage(response, response.href);
	
	Toastify({
		text: toastBody.innerHTML,
		duration: 2000,
		position: "center",
		escapeMarkup: false,
		className: toastBody.getAttribute("state"),
	}).showToast();
	
	const toastLocation = toastBody.getAttribute("location");
	
	if(toastLocation.length) getPage(toastLocation);
	else dashboardLoader.classList.add("hide");
}