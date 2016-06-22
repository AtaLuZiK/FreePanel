#include "freepaneld.h"
#include "FPPacket.h"
#include <string>
#include <cstring>

const uint16_t FPPacket::SIGN = 0x5046;
const uint16_t FPPacket::MIN_SUPPORTED_VERSION = 1;

FPPacket::FPPacket()
    : m_Entity(new std::string)
    , m_Data(new std::string)
{
    memset(&m_Header, 0, sizeof(Header));
    memset(&m_Footer, 0, sizeof(Footer));
    m_Header.sign = SIGN;
    m_Header.version = FREEPANELD_VERSION_INT;
}


FPPacket::FPPacket(Header *pHeader, Footer *pFooter, byte *entity)
    : m_Entity(new std::string)
    , m_Data(new std::string)
{
    memcpy(&m_Header, pHeader, sizeof(Header));
    memcpy(&m_Footer, pFooter, sizeof(Footer));
    STREAM_PTR(m_Entity)->assign((const char *)entity, pHeader->entitySize);
}


FPPacket::~FPPacket()
{
    delete STREAM_PTR(m_Entity);
    delete STREAM_PTR(m_Data);
}


void FPPacket::Append(const char *data, int sizeInBytes)
{
    if (sizeInBytes == -1)
        sizeInBytes = strlen(data);
    STREAM_PTR(m_Entity)->append((const char *)data, sizeInBytes);
}


void FPPacket::AppendUInt8(uint8_t n)
{
    Append((const char *)&n, sizeof(uint8_t));
}


void FPPacket::Empty()
{
    STREAM_PTR(m_Entity)->clear();
}


const unsigned char *FPPacket::Generate()
{
    const int HEADER_SIZE = sizeof(Header);
    const int FOOTER_SIZE = sizeof(Footer);
    STREAM_PTR(m_Data)->clear();
    m_Header.entitySize = STREAM_PTR(m_Entity)->size();
    STREAM_PTR(m_Data)->append((const char *)&m_Header, HEADER_SIZE);
    STREAM_PTR(m_Data)->append(*STREAM_PTR(m_Entity));
    STREAM_PTR(m_Data)->append((const char *)&m_Footer, FOOTER_SIZE);
    return (unsigned char *)STREAM_PTR(m_Data)->data();
}


unsigned int FPPacket::GetSize() const
{
    return STREAM_PTR(m_Data)->size();
}


const char *FPPacket::GetEntity() const
{
    return STREAM_PTR(m_Entity)->c_str();
}


unsigned int FPPacket::GetEntitySize() const
{
    return STREAM_PTR(m_Entity)->size();
}


uint8_t FPPacket::GetOrder() const
{
    return m_Header.order;
}


void FPPacket::SetOrder(uint8_t order)
{
    m_Header.order = order;
}


FPPacket::Command FPPacket::GetCommand() const
{
    return (Command)m_Header.command;
}


void FPPacket::SetCommand(Command cmd)
{
    m_Header.command = (uint8_t)cmd;
}


bool FPPacket::CheckHeader(Header *pHeader)
{
    if (pHeader->sign != SIGN)
        return false;
    if (pHeader->version > MIN_SUPPORTED_VERSION || pHeader->version < 1)
        return false;
    return true;
}
