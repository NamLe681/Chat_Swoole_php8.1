<template>
  <div class="voice-recorder">
    <v-btn color="primary" @click="toggleRecording">
      {{ isRecording ? "⏹ Dừng ghi" : "🎙 Bắt đầu ghi" }}
    </v-btn>

    <!-- <audio v-if="audioUrl" :src="audioUrl" controls class="mt-3"></audio> -->
  </div>
</template>

<script>
import axios from "axios";

export default {
  props: {
    roomId: { type: Number, required: true },
  },
  data() {
    return {
      mediaRecorder: null,
      audioChunks: [],
      isRecording: false,
      audioUrl: null,
    };
  },
  methods: {
    async toggleRecording() {
      if (this.isRecording) {
        this.stopRecording();
      } else {
        await this.startRecording();
      }
    },

    async startRecording() {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        this.mediaRecorder = new MediaRecorder(stream);
        this.audioChunks = [];

        this.mediaRecorder.ondataavailable = (e) => {
          this.audioChunks.push(e.data);
        };

        this.mediaRecorder.onstop = this.uploadVoice;
        this.mediaRecorder.start();
        this.isRecording = true;

        console.log("🎙 Đang ghi âm...");
      } catch (error) {
        alert("Không thể ghi âm: " + error.message);
      }
    },

    stopRecording() {
      this.isRecording = false;
      this.mediaRecorder.stop();
      console.log("⏹ Dừng ghi âm");
    },

    async uploadVoice() {
      const mimeType = this.mediaRecorder.mimeType || 'audio/webm';
      const blob = new Blob(this.audioChunks, { type: mimeType });
      console.log("Blob type:", blob.type);

      const formData = new FormData();
      formData.append("voice", blob, `voice.${mimeType.split("/")[1]}`);
      formData.append("room_id", this.roomId);

      console.log("🎧 Uploading voice message for room:", this.roomId, "MIME:", mimeType);

      try {
        const res = await axios.post(`/api/messages/voice/${this.roomId}`, formData, {
          headers: { "Content-Type": "multipart/form-data" },
          withCredentials: true,
        });

        this.audioUrl = res.data.url;
        this.$emit("voiceSent", res.data.data);
        console.log("🎧 Voice uploaded URL:", this.audioUrl);

        console.log("✅ Voice uploaded:", res.data);
      } catch (error) {
        console.error("❌ Upload thất bại:", error.response || error);
      }
    },



  },
};
</script>

<style scoped>
.voice-recorder {
  padding:5px;
  position: absolute;
  background: #3498db;
  color: white;
  right: 355px;
  bottom: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
