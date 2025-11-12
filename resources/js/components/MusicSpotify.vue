<template>
  <div class="spotify-search">
    <!-- Ô nhập và nút tìm -->
    <div class="search-bar-wrapper">
      <div class="search-bar">
        <input v-model="query" type="text" placeholder="Nhập tên bài hát..." @keyup.enter="searchSpotify" />
        <button @click="searchSpotify">Tìm nhạc</button>
        <button v-if="tracks.length" @click="toggleResults" class="toggle-btn">
          {{ showResults ? '-' : '+' }}
        </button>
      </div>

      <!-- Danh sách kết quả (toggle, position absolute) -->
      <transition name="fade">
        <div v-if="showResults && tracks.length" class="results">
          <ul>
            <li v-for="track in tracks" :key="track.id" @click="selectTrack(track)"
              :class="{ selected: selectedTrack?.id === track.id }">
              <img :src="track.image" alt="album cover" width="50" height="50" />
              <div class="info">
                <p class="name">{{ track.name }}</p>
                <p class="artist">{{ track.artist }} - {{ track.album }}</p>
              </div>
            </li>
          </ul>
        </div>
      </transition>
    </div>

    <!-- Nút gửi nhạc -->
    <div v-if="selectedTrack" class="send-section">
      <p>Bạn đã chọn: <strong>{{ selectedTrack.name }}</strong></p>
      <button @click="sendTrack(selectedTrack)">Gửi nhạc này</button>
    </div>

  </div>

</template>

<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex';

const store = useStore();
const query = ref('');
const tracks = ref([]);
const selectedTrack = ref(null);
const showResults = ref(false);


const searchSpotify = async () => {
  if (!query.value.trim()) return;

  try {
    const res = await store.dispatch('searchspotify', { q: query.value });
    tracks.value = res?.tracks || [];
    selectedTrack.value = null;
    if (tracks.value.length) showResults.value = true;
    console.log('Đã tìm nhạc:', tracks.value);
  } catch (error) {
    console.error('Lỗi tìm nhạc Spotify:', error);
  }
};

const toggleResults = () => {
  showResults.value = !showResults.value;
};


const selectTrack = (track) => {
  selectedTrack.value = track;
  console.log('đã chọn bài', track.id)
  showResults.value = false;
};


const sendTrack = async (track) => {
  if (!track || !track.id) {
    console.error("❌ Track id không tồn tại!", track);
    return;
  }

  const trackId = track.id;
  console.log("🎧 Selected ID:", trackId);

  try {
    const res = await axios.get(`/api/spotify/track/${trackId}`);
    console.log(" Track data:", res.data.name);
    const sendmessage = await axios.post(`/api/spotify/music/8/`, {
      content: res.data.name,
      type: 'music',
      track: res.data.name,
    });
    this.$emit("selectedTrack", sendmessage);
  } catch (error) {
    console.error(" Upload thất bại:", error.response || error);
  }
};



</script>

<style scoped>
.spotify-search {
  max-width: 400px;
  margin: 0 auto;
  position: relative;
}

.search-bar-wrapper {
  position: relative;
}

.search-bar {
  display: flex;
  gap: 8px;
}

.toggle-btn {
  background: #ddd;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}

/* Danh sách kết quả */
.results {
  position: absolute;
  bottom: 100%;
  left: 0;
  width: 100%;
  max-height: 250px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  margin-top: 4px;
  padding: 5px 0;
}

.results ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.results li {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 10px;
  cursor: pointer;
  transition: background 0.2s;
}

.results li:hover {
  background: #f0f0f0;
}

.results li.selected {
  background: #c6f6d5;
}

.info .name {
  font-weight: bold;
}

.send-section {
  margin-top: 10px;
  text-align: center;
}

/* Hiệu ứng fade toggle */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>