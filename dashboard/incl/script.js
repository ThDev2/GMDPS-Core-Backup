if(typeof localStorage.navbar_state == "undefined") localStorage.navbar_state = 'true';
if(typeof localStorage.player_volume == "undefined") localStorage.player_volume = 0.2;

var dashboardLoader, dashboardBody, dashboardBase;

window.addEventListener('load', () => {
	dashboardLoader = document.getElementById("dashboard-loader");
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	
	if(localStorage.navbar_state == 'true') dashboardBody.classList.add("hide");
	else dashboardBody.classList.remove("hide");
	
	updateNavbar();
	
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
	
	await changePage(response, href);
	
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
	
	const newPage = newPageBody.getElementById("dashboard-page");
	
	if(newPage == null) {
		const toastBody = newPageBody.getElementById("toast");
		if(toastBody != null) return showToast(toastBody);
		
		Toastify({
			text: failedToLoadText,
			duration: 2000,
			position: "center",
			escapeMarkup: false,
			className: 'error',
		}).showToast();
		
		return;
	}

	if(!skipCheck) history.pushState(null, null, href);
	
	document.getElementById("dashboard-page").replaceWith(newPage);
	document.querySelector("base").replaceWith(newPageBody.querySelector("base"));
	document.querySelector("title").replaceWith(newPageBody.querySelector("title"));
	document.querySelector("nav").replaceWith(newPageBody.querySelector("nav"));
	
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	
	updateNavbar();
}

function updateNavbar() {
	const navbarButtons = document.querySelectorAll("nav button");
	
	navbarButtons.forEach(navbarButton => {
		const href = navbarButton.getAttribute("href");
		const dropdown = navbarButton.getAttribute("dashboard-dropdown");
		
		if(href != null || dropdown != null) navbarButton.addEventListener("mouseup", (event) => {
			if(dropdown != null) return toggleDropdown(dropdown);
			
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
	
	for(const element of document.querySelectorAll("[dashboard-hide=true]")) element.remove();
	for(const element of document.querySelectorAll("[dashboard-show=false]")) element.remove();
}

function toggleDropdown(dropdown) {
	document.getElementById(dropdown).classList.toggle("show");
}

function showToast(toastBody) {
	Toastify({
		text: toastBody.innerHTML,
		duration: 2000,
		position: "center",
		escapeMarkup: false,
		className: toastBody.getAttribute("state"),
	}).showToast();
	
	const toastLocation = toastBody.getAttribute("location");
	if(toastLocation.length) getPage(toastLocation);
}