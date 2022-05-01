var aud = {
	// (A) PROPERTIES
	now : 0, // current song
	player : null, // reference to HTML <audio> element
	playlist : null, // reference to HTML playlist
	
	// (B) INITIALIZE PLAYER
	init : function () {
		// (B1) GET ALL SONGS
		aud.playlist = document.querySelectorAll("#ListPlayer .song");
		
		// (B2) PROCEED SETUP ONLY IF THERE ARE SONGS TO PLAY
		if (aud.playlist.length>0) {
			// GET AUDIO TAG
			aud.player = document.getElementById("PlayerAudio");
			
			// CLICK ON SONG TO PLAY
			for (let song of aud.playlist) { song.onclick = aud.play; }
			
			// AUTOPLAY NEXT SONG IN PLAYLIST WHEN CURRENT SONG ENDS
			aud.player.onended = function() {
				aud.now++;
				if (aud.now>=aud.playlist.length) { aud.now = 0; }
				aud.playlist[aud.now].click();
			};
			
			// AUTOPLAY FIRST SONG
			aud.playlist[0].click();
		}
	},
	
	// (C) START PLAYING
	play : function () {
		// (C1) UPDATE CURRENT & PLAY
		aud.now = this.dataset.id;
		aud.player.src = this.dataset.src;
		aud.player.play();
		
		// (C2) A LITTLE BIT OF COSMETIC
		for (let song of aud.playlist) {
			if (song===this) { song.style.backgroundColor = "LightGray"; }
			else { song.style.backgroundColor = "initial"; }
		}
	}
};
window.addEventListener("DOMContentLoaded", aud.init);


// Christophe Caron
// Domotronic.fr 2022

function next() {
	if (aud.now<aud.playlist.length-1) {
        aud.now++;
	}
	aud.playlist[aud.now].click();
	aud.player.play();
}
window.addEventListener('load', event => {
	next();
});

function prev() {
	if (aud.now> 0) {
        aud.now--;
	}
	aud.playlist[aud.now].click();
	aud.player.play();
}
window.addEventListener('load', event => {
	prev();
});