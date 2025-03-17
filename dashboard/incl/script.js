if(typeof localStorage.navbar_state == "undefined") localStorage.navbar_state = 'true';
if(typeof localStorage.player_volume == "undefined") localStorage.player_volume = 0.2;

var dashboardLoader, dashboardBody, dashboardBase;

window.addEventListener('load', () => {
	dashboardLoader = document.getElementById("dashboard-loader");
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	
	if(localStorage.navbar_state == 'true') dashboardBody.classList.add("hide");
	else dashboardBody.classList.remove("hide");
	
	updatePage();
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
	
	updatePage();
	updateNavbar();
}

async function updateNavbar() {
	const navbarButtons = document.querySelectorAll("nav button");
	
	navbarButtons.forEach(navbarButton => {
		const href = navbarButton.getAttribute("href");
		const dropdown = navbarButton.getAttribute("dashboard-dropdown");
		
		if(href != null && ((window.location.href.endsWith(href) && href.length) || (!href.length && dashboardBase.getAttribute("href") == './'))) navbarButton.classList.add("current");
		
		if(dropdown != null) navbarButton.addEventListener("mouseup", (event) => toggleDropdown(dropdown));
	});
	
	for(const element of document.querySelectorAll("[dashboard-hide=true]")) element.remove();
	for(const element of document.querySelectorAll("[dashboard-show=false]")) element.remove();
}

function toggleDropdown(dropdown) {
	const previousDropdown = document.querySelector(".dropdown.show");
	if(previousDropdown != null && previousDropdown.id != dropdown) previousDropdown.classList.remove("show");
	
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

async function updatePage() {
	const removeElements = dashboardBody.querySelectorAll('[dashboard-remove]');
	removeElements.forEach(async (element) => {
		const elementsToRemove = element.getAttribute("dashboard-remove").split(" ");
		
		elementsToRemove.forEach(async (remove) => element.removeAttribute(remove));
		
		element.removeAttribute("dashboard-remove");
	});
	
	const copyElements = dashboardBody.querySelectorAll('[dashboard-copy]');
	copyElements.forEach(async (element) => {
		const textToCopy = element.innerHTML;
		
		element.addEventListener("click", async (event) => {
			navigator.clipboard.writeText(textToCopy);
			
			Toastify({
				text: copiedText,
				duration: 2000,
				position: "center",
				escapeMarkup: false,
				className: "success",
			}).showToast();
		});
	});
	
	const hrefElements = document.querySelectorAll('[href]');
	hrefElements.forEach(async (element) => {
		const href = element.getAttribute("href");
		
		element.addEventListener("mouseup", async (event) => {
			switch(event.button) {
				case 0:
					getPage(href);
					break;
				case 1:
					const openNewTab = document.createElement("a");
					openNewTab.href = href;
					openNewTab.target = "_blank";
					openNewTab.click();
					return false;
					break;
			}
		});
	});
	
	const disableElements = dashboardBody.querySelectorAll('[dashboard-disable]');
	disableElements.forEach(async (element) => {
		const isDisable = element.getAttribute("dashboard-disable");
		
		if(isDisable == 'true') element.disabled = true;
	});
}