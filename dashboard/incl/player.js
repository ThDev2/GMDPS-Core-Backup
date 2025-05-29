var player;

function loadAudioPlayer() {
	player = document.getElementById("audioPlayer");

	player.audio = document.getElementById("audioPlayerSong");
	player.songAuthor = document.getElementById("audioPlayerAuthor");
	player.songTitle = document.getElementById("audioPlayerTitle");
	player.songID = document.getElementById("audioPlayerSongID");
	player.currentTime = document.getElementById("audioPlayerCurrentTime");
	player.totalTime = document.getElementById("audioPlayerTotalTime");
	player.downloadButton = document.getElementById("audioPlayerDownloadButton");
	player.range = document.getElementById("audioPlayerRange");
	player.playButton = document.getElementById("audioPlayerPlayButton");
	player.playButtonIcon = player.playButton.querySelector("i");

	player.isPlaying = false;
	player.pausedByUser = false;
	player.songURL = '';
	player.interval = false;
	player.current = {
		interact: () => {}
	}

	player.start = async function(songID, songAuthor, songTitle, songURL) {
		player.classList.add("show");
		
		player.songURL = songURL;
		
		player.audio.src = decodeURIComponent(songURL);
		player.audio.volume = localStorage.player_volume;
		
		player.songAuthor.innerHTML = "";
		player.songTitle.innerHTML = "...";
		player.songID.innerHTML = "";
		
		player.audio.onloadedmetadata = async function() {
			player.isPlaying = songID;
			
			player.songAuthor.innerHTML = escapeHTML(songAuthor);
			player.songTitle.innerHTML = escapeHTML(songTitle) + " •";
			player.songID.innerHTML = escapeHTML(songID);
			player.songID.onclick = () => copyElementContent(songID);
			player.downloadButton.onclick = () => downloadSong(songAuthor, songTitle, songURL);
			player.current.interact = () => player.interact(songID, songAuthor, songTitle, songURL);
			
			player.play();
			
			player.interval = setInterval(() => player.changeRange(player.audio.currentTime, player.audio.duration), 100);
			player.playButton.onclick = () => player.interact(songID, songAuthor, songTitle, songURL);
			
			player.range.addEventListener("input", (event) => {
				player.audio.currentTime = event.target.value / 1000000;
				
				if(player.audio.paused && !player.pausedByUser) player.play();
			});
		}
	}

	player.interact = async function(songID, songAuthor, songTitle, songURL) {
		if(!player.isPlaying || player.songURL != songURL) {
			player.stop();
			return player.start(songID, songAuthor, songTitle, songURL);
		}
		
		if(player.audio.paused) player.play();
		else player.pause();
	}

	player.play = async function() {
		player.playButtonIcon.classList.replace("fa-play", "fa-pause");
		document.querySelectorAll("[dashboard-song='" + player.isPlaying + "'] i").forEach((element) => element.classList.replace("fa-circle-play", "fa-circle-pause"));
		
		player.pausedByUser = false;
		
		player.audio.play();
	}

	player.pause = async function() {
		player.playButtonIcon.classList.replace("fa-pause", "fa-play");
		document.querySelectorAll("[dashboard-song='" + player.isPlaying + "'] i").forEach((element) => element.classList.replace("fa-circle-pause", "fa-circle-play"));
		
		player.pausedByUser = true;

		player.audio.pause();
	}

	player.stop = async function() {
		player.classList.remove("show");
		
		player.audio.src = "";
		
		player.songAuthor.innerHTML = "";
		player.songTitle.innerHTML = nothingIsPlayingText;
		player.songID.innerHTML = "";
		player.songID.onclick = () => {};
		player.downloadButton.onclick = () => {};
		player.current.interact = () => {};
		
		player.playButtonIcon.classList.replace("fa-pause", "fa-play");
		document.querySelectorAll("[dashboard-song='" + player.isPlaying + "'] i").forEach((element) => element.classList.replace("fa-circle-pause", "fa-circle-play"));
		
		player.isPlaying = false;
		
		clearInterval(player.interval);
		player.playButton.onclick = () => {};
		
		player.changeRange(0, 0);
	}

	player.changeRange = async function(current, total) {
		player.range.value = current * 1000000;
		player.range.max = total * 1000000;
		
		player.currentTime.innerHTML = await player.convertTime(current);
		player.totalTime.innerHTML = await player.convertTime(total);
		
		if(current == total) {
			player.playButtonIcon.classList.replace("fa-pause", "fa-play");
			document.querySelectorAll("[dashboard-song='" + player.isPlaying + "'] i").forEach((element) => element.classList.replace("fa-circle-pause", "fa-circle-play"));
		}
	}

	player.convertTime = async function(time) { // https://stackoverflow.com/a/36981712
		if(time == 0 || isNaN(time)) return "~:~";

		var seconds = Math.round(time % 60);
		var foo = time - seconds;
		var minutes = Math.round(foo / 60);
		
		if(seconds == 60) {
			seconds = 0;
			minutes++;
		}
		
		if(seconds < 10) seconds = "0" + seconds.toString();
		
		return minutes + ":" + seconds;
	}

	window.addEventListener("keydown", function(e) {
		switch(e.key) {
			case "MediaPlayPause":
				player.current.interact();
				return false;
				break;
			case "MediaStop":
				player.stop();
				return false;
				break;
			default:
				return true;
				break;
		}
	});
}