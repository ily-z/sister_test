<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $icon
 * @property string $color
 * @property string $title
 * @property string $description
 * @property string|null $user
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $userRelation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUserId($value)
 */
	class Activity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nomor_pesanan Auto-generated: YYYYMMDD-0001
 * @property string $nama_pelanggan
 * @property string $kontak_pelanggan
 * @property string|null $email
 * @property array<array-key, mixed>|null $foto_ktp
 * @property array<array-key, mixed>|null $foto_penyewa
 * @property \Illuminate\Support\Carbon $tanggal_mulai
 * @property \Illuminate\Support\Carbon $tanggal_selesai
 * @property string|null $lokasi_jemput
 * @property string|null $lokasi_antar
 * @property string $tipe_sewa harian|kontrak
 * @property string $status_pembayaran Belum Bayar|DP|Belum Lunas|Lunas|Refund Pending|Refunded
 * @property string $status_rental Menunggu|Aktif|Selesai|Batal
 * @property numeric|null $total_harga Override total untuk kontrak
 * @property numeric $total_addons
 * @property numeric $dp_amount
 * @property numeric $refund_amount Total dana yang sudah direfund
 * @property string|null $foto_pendukung
 * @property string|null $catatan
 * @property string|null $picked_up_at
 * @property string|null $returned_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookingAddon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookingCar> $bookingCars
 * @property-read int|null $booking_cars_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereDpAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereFotoKtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereFotoPendukung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereFotoPenyewa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereKontakPelanggan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereLokasiAntar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereLokasiJemput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereNamaPelanggan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereNomorPesanan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking wherePickedUpAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereRefundAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereReturnedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereStatusPembayaran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereStatusRental($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereTanggalMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereTanggalSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereTipeSewa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereTotalAddons($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereTotalHarga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereUpdatedAt($value)
 */
	class Booking extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property string $nama_biaya
 * @property numeric $nominal
 * @property-read \App\Models\Booking $booking
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon whereNamaBiaya($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingAddon whereNominal($value)
 */
	class BookingAddon extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property int $car_id
 * @property int $quantity
 * @property numeric $harga_dasar_per_hari
 * @property string|null $catatan_harga
 * @property array<array-key, mixed>|null $foto_kondisi Menyimpan array path foto kondisi awal mobil
 * @property-read \App\Models\Booking $booking
 * @property-read \App\Models\Car $car
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereCarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereCatatanHarga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereFotoKondisi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereHargaDasarPerHari($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingCar whereQuantity($value)
 */
	class BookingCar extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property numeric $jumlah
 * @property string $status_sebelum
 * @property string $status_sesudah
 * @property string|null $metode_bayar tunai|transfer|dll
 * @property string|null $catatan
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\Booking $booking
 * @property-read \App\Models\User|null $createdBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereJumlah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereMetodeBayar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereStatusSebelum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingPayment whereStatusSesudah($value)
 */
	class BookingPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $plat_nomor
 * @property string $merk
 * @property string|null $model
 * @property string|null $warna
 * @property string $tahun
 * @property int $kapasitas jumlah penumpang
 * @property numeric $harga_default Harga sewa dasar per hari
 * @property numeric $harga_kontrak Harga sewa per hari khusus kontrak
 * @property string|null $foto
 * @property string|null $no_mesin
 * @property string|null $no_rangka
 * @property int $km_saat_ini
 * @property string $status Tersedia|Sedang Disewa|Masuk Bengkel
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OdometerLog> $odometerLogs
 * @property-read int|null $odometer_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CarPart> $parts
 * @property-read int|null $parts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceLog> $serviceLogs
 * @property-read int|null $service_logs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereFoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereHargaDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereHargaKontrak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereKapasitas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereKmSaatIni($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereMerk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereNoMesin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereNoRangka($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car wherePlatNomor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Car whereWarna($value)
 */
	class Car extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $car_id
 * @property int|null $master_part_id
 * @property string $nama_part
 * @property string $icon
 * @property int $is_track_km
 * @property int|null $lifespan_km Target umur KM part ini pada mobil ini
 * @property int $last_service_km Posisi Odometer saat part ini dipasang/diservis
 * @property string|null $last_service_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Car $car
 * @property-read \App\Models\MasterPart|null $masterPart
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereCarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereIsTrackKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereLastServiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereLastServiceKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereLifespanKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereMasterPartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereNamaPart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarPart whereUpdatedAt($value)
 */
	class CarPart extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama_part
 * @property string $kategori Mesin|Kaki-kaki|Aksesoris|Interior
 * @property string $icon Material symbol icon name
 * @property int $is_track_km True = hitung KM, False = Aksesoris statis
 * @property int|null $default_lifespan_km Rekomendasi umur KM. Null jika is_track_km = false
 * @property string|null $deskripsi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereDefaultLifespanKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereIsTrackKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereKategori($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereNamaPart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterPart whereUpdatedAt($value)
 */
	class MasterPart extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $car_id
 * @property int $km_reading
 * @property string|null $created_at
 * @property-read \App\Models\Car $car
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog whereCarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OdometerLog whereKmReading($value)
 */
	class OdometerLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama_permission
 * @property string|null $deskripsi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereNamaPermission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $car_id
 * @property string|null $nama_bengkel
 * @property string|null $foto_nota
 * @property string|null $catatan
 * @property int $km_saat_servis
 * @property string $tanggal_masuk
 * @property string|null $tanggal_keluar
 * @property string $status_bengkel open|closed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Car $car
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceLogDetail> $details
 * @property-read int|null $details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceLogDetail> $serviceLogDetails
 * @property-read int|null $service_log_details_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereCarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereFotoNota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereKmSaatServis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereNamaBengkel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereStatusBengkel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereTanggalKeluar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereTanggalMasuk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLog whereUpdatedAt($value)
 */
	class ServiceLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $service_log_id
 * @property int|null $car_part_id
 * @property string $deskripsi_tindakan Contoh: Ganti Oli Shell 10W-40
 * @property numeric $biaya
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CarPart|null $carPart
 * @property-read \App\Models\ServiceLog $serviceLog
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereBiaya($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereCarPartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereDeskripsiTindakan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereServiceLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceLogDetail whereUpdatedAt($value)
 */
	class ServiceLogDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $tipe Pemasukan|Pengeluaran
 * @property string $kategori sewa|servis|operasional_mobil|kantor
 * @property numeric $jumlah
 * @property string $tanggal
 * @property string|null $deskripsi
 * @property string|null $referensi_type booking|service_log|null
 * @property int|null $referensi_id
 * @property int|null $car_id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Car|null $car
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $referensi
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereCarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereJumlah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereKategori($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereReferensiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereReferensiType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereTipe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction whereUserId($value)
 */
	class Transaction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \App\Enums\UserRole $role
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

