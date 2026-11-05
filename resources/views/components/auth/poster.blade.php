{{-- Poster logistics của trang đăng nhập (dựng theo logistics-poster.html). Chuyển động: resources/js/login/poster.js --}}
@php($logo = asset('images/poster-logo.png'))
<div class="poster" data-poster>
    {{-- Lớp tràn ra ngoài khung poster: bàn phím chạy hết mép bảng bên trái --}}
    <div class="poster__bleed"><x-auth.keyboard /></div>

    <div class="poster__stage">
        <div class="calendar">
            <div class="line v" style="left:290px; top:250px; height:830px"></div>
            <div class="line v" style="left:705px; top:330px; height:750px"></div>
            <div class="line h" style="left:-40px; top:420px; width:1160px; transform:rotate(-3deg)"></div>
            <div class="line h" style="left:-40px; top:740px; width:1160px; transform:rotate(4deg)"></div>
            <div class="num" style="left:130px; top:285px">12</div>
            <div class="num" style="left:315px; top:255px">13</div>
            <div class="num" style="left:130px; top:455px">15</div>
            <div class="num" style="left:315px; top:440px">16</div>
            <div class="num big" style="left:740px; top:415px">17</div>
            <div class="num" style="left:130px; top:735px">18</div>
            <div class="num big" style="left:735px; top:800px">20</div>
        </div>

        <div class="headline">SYTEK<br>MOVING ON TIME.</div>

        <div class="wind" data-wind></div>

        <div class="truck" data-truck role="button" tabindex="0" aria-pressed="false" aria-label="Bật hoặc tắt đèn xe" title="Bấm để bật/tắt đèn xe">
            <div class="shadow"></div>
            <div class="side"><div class="mini"><img src="{{ $logo }}" alt=""><span>SY<br>LOGISTICS CO.</span></div></div>
            <div class="cab"></div>
            <div class="window"></div>
            <div class="mirror"></div>
            <div class="headlamp"></div>
            <div class="beam"></div>
            <div class="beam-text">Delivery</div>
            <div class="back">
                <div class="opening">
                    <div class="shutter"></div>
                    <div class="box" style="left:68px; top:115px; width:110px; height:75px"></div>
                    <div class="box" style="left:15px; top:200px; width:58px; height:100px"></div>
                    <div class="box" style="left:73px; top:190px; width:62px; height:110px"></div>
                    <div class="box" style="left:135px; top:180px; width:60px; height:120px"></div>
                </div>
            </div>
            <div class="wheel" style="left:15px; top:355px; width:80px; height:80px; --sx:1"></div>
            <div class="wheel" style="left:225px; top:350px; width:80px; height:85px; --sx:.94"></div>
            <div class="wheel" style="left:365px; top:305px; width:55px; height:85px; --sx:.65"></div>
            <div class="mudflap" style="left:5px"></div>
            <div class="mudflap" style="left:165px"></div>
            <div class="bumper">
                <div class="plate"></div>
                <div class="light amber" style="left:4px"></div>
                <div class="light red" style="left:42px"></div>
                <div class="light red" style="left:150px"></div>
                <div class="light amber" style="left:190px"></div>
            </div>
        </div>

        <div class="papers" data-papers></div>

        <div class="contact">
            <div class="contact__item">
                <svg width="48" height="34" viewBox="0 0 48 34"><rect x="1" y="1" width="46" height="32" rx="3" fill="#ffb938"/><path d="M2 2 L24 20 L46 2" stroke="#232e5f" stroke-width="3" fill="none"/></svg>
                <div><div class="contact__label">EMAIL US AT</div><div class="contact__value">Ha@vnsytek.com</div></div>
            </div>
            <div class="contact__phone">
                <div class="contact__label">PHONE</div>
                <div class="contact__value">
                    <svg width="36" height="36" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="#ffb938" stroke-width="2"/><path d="M8 6c-1 1-1 3 1 6s4 5 6 5c1 0 2-1 2-2l-2-2-2 1c-1-1-2-2-3-4l1-2-2-2z" fill="#ffb938"/></svg>
                    0397-040-799
                </div>
            </div>
        </div>
    </div>
</div>
